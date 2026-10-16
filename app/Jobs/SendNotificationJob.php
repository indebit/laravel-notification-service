<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\DeliveryService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Up to five delivery attempts, spaced by the backoff, within an hour. Rate limit releases do not
 * count as attempts, because the job is bounded by retryUntil() and MaxExceptions instead of a
 * fixed number of tries.
 */
#[Backoff(5, 30, 120, 600)]
#[Timeout(30)]
#[MaxExceptions(5)]
class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Notification $notification)
    {
        $this->onQueue($notification->priority->queue());
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('notification-channel')];
    }

    public function retryUntil(): DateTime
    {
        return now()->addHour()->toDateTime();
    }

    public function handle(DeliveryService $deliveryService): void
    {
        $notification = $this->notification;

        if (! in_array($notification->status, [NotificationStatus::Pending, NotificationStatus::Processing], true)) {
            return;
        }

        Log::shareContext([
            'correlation_id' => $notification->id,
            'channel' => $notification->channel->value,
        ]);

        $deliveryService->deliver($notification);

        Log::info('Notification delivered.', [
            'notification_id' => $notification->id,
            'attempts' => $notification->attempt_count,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $notification = $this->notification->fresh();

        if ($notification === null) {
            return;
        }

        app(DeliveryService::class)->markFailed(
            $notification,
            $exception?->getMessage() ?? 'Delivery gave up after the final attempt.',
        );
    }
}
