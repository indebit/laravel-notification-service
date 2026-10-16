<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function __construct(private TemplateService $templateService) {}

    /**
     * @param  array<string, mixed>  $notificationValidatedData
     */
    public function create(array $notificationValidatedData): Notification
    {
        $notification = $this->persist($notificationValidatedData);
        $this->dispatchUnlessScheduled($notification);

        return $notification;
    }

    /**
     * Create every notification in the batch or none of them.
     *
     * Jobs are only dispatched once the whole batch is stored, so a rejected batch never queues work.
     *
     * @param  array<int, array<string, mixed>>  $notifications
     * @return array{batch_id: string, notifications: Collection<int, Notification>}
     */
    public function createBatch(array $notifications): array
    {
        $batchId = Str::uuid()->toString();

        $created = DB::transaction(function () use ($notifications, $batchId): Collection {
            /** @var Collection<int, Notification> $created */
            $created = new Collection;

            foreach ($notifications as $index => $data) {
                try {
                    $created->push($this->persist([...$data, 'batch_id' => $batchId]));
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(collect($exception->errors())
                        ->mapWithKeys(fn (array $messages, string $key): array => ["notifications.{$index}.{$key}" => $messages])
                        ->all());
                }
            }

            return $created;
        });

        $created->each(fn (Notification $notification) => $this->dispatchUnlessScheduled($notification));

        return [
            'batch_id' => $batchId,
            'notifications' => $created,
        ];
    }

    public function cancel(Notification $notification): Notification
    {
        // Conditional update, so a worker that picks the notification up at the same moment wins.
        $cancelled = Notification::query()
            ->whereKey($notification->getKey())
            ->where('status', NotificationStatus::Pending)
            ->update(['status' => NotificationStatus::Cancelled]);

        if ($cancelled === 0) {
            throw ValidationException::withMessages([
                'notification' => ['Only pending notifications can be cancelled.'],
            ]);
        }

        return $notification->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Notification>
     */
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $from = isset($filters['from']) ? Carbon::parse((string) $filters['from']) : null;
        $to = isset($filters['to']) ? Carbon::parse((string) $filters['to']) : null;

        $query = Notification::query()
            ->when(isset($filters['status']), fn ($q) => $q->byStatus(NotificationStatus::from((string) $filters['status'])))
            ->when(isset($filters['channel']), fn ($q) => $q->byChannel(NotificationChannel::from((string) $filters['channel'])))
            ->byDateRange($from, $to)
            ->when(isset($filters['batch_id']), fn ($q) => $q->byBatchId((string) $filters['batch_id']))
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data): Notification
    {
        if (isset($data['template_name'])) {
            $data['content'] = $this->templateService->render(
                (string) $data['template_name'],
                (array) ($data['template_variables'] ?? []),
                NotificationChannel::from((string) $data['channel']),
            );
        }

        $priority = $data['priority'] ?? NotificationPriority::Normal->value;
        $attributes = [
            ...$data,
            'status' => NotificationStatus::Pending,
            'priority' => $priority instanceof NotificationPriority ? $priority : NotificationPriority::from((string) $priority),
        ];

        $idempotencyKey = $data['idempotency_key'] ?? null;

        // Insert first and let the unique index decide: a repeated or concurrent request with the
        // same key gets the stored row back instead of a constraint violation.
        return is_string($idempotencyKey)
            ? Notification::createOrFirst(['idempotency_key' => $idempotencyKey], $attributes)
            : Notification::create($attributes);
    }

    private function dispatchUnlessScheduled(Notification $notification): void
    {
        if ($notification->wasRecentlyCreated && $notification->scheduled_at === null) {
            $notification->update(['queued_at' => now()]);
            SendNotificationJob::dispatch($notification);
        }
    }
}
