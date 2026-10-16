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
        $idempotencyKey = $notificationValidatedData['idempotency_key'] ?? null;

        if (is_string($idempotencyKey)) {
            $existingNotification = Notification::where('idempotency_key', $idempotencyKey)->first();

            if ($existingNotification instanceof Notification) {
                return $existingNotification;
            }
        }

        if (isset($notificationValidatedData['template_name'])) {
            $notificationValidatedData['content'] = $this->templateService->render(
                (string) $notificationValidatedData['template_name'],
                (array) ($notificationValidatedData['template_variables'] ?? []),
                NotificationChannel::from((string) $notificationValidatedData['channel']),
            );
        }

        $priority = $notificationValidatedData['priority'] ?? NotificationPriority::Normal->value;
        $priorityEnum = $priority instanceof NotificationPriority
            ? $priority
            : NotificationPriority::from((string) $priority);

        $notification = Notification::create([
            ...$notificationValidatedData,
            'status' => NotificationStatus::Pending,
            'priority' => $priorityEnum,
        ]);

        if ($notification->scheduled_at === null) {
            SendNotificationJob::dispatch($notification)->onQueue($this->queueName($notification->priority));
        }

        return $notification;
    }

    /**
     * @param  array<int, array<string, mixed>>  $notifications
     * @return array{batch_id: string, notifications: Collection<int, Notification>}
     */
    public function createBatch(array $notifications): array
    {
        $batchId = Str::uuid()->toString();
        /** @var Collection<int, Notification> $createdNotifications */
        $createdNotifications = new Collection;

        foreach ($notifications as $data) {
            $notification = $this->create([
                ...$data,
                'batch_id' => $batchId,
            ]);

            $createdNotifications->push($notification);
        }

        return [
            'batch_id' => $batchId,
            'notifications' => $createdNotifications,
        ];
    }

    public function cancel(Notification $notification): Notification
    {
        if ($notification->status !== NotificationStatus::Pending) {
            throw ValidationException::withMessages([
                'notification' => ['Only pending notifications can be cancelled.'],
            ]);
        }

        $notification->update([
            'status' => NotificationStatus::Cancelled,
        ]);

        return $notification->fresh() ?? $notification;
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

    private function queueName(NotificationPriority $priority): string
    {
        return match ($priority) {
            NotificationPriority::High => 'high',
            NotificationPriority::Low => 'low',
            NotificationPriority::Normal => 'default',
        };
    }
}
