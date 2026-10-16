<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\DeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Facades\Log;

#[Tries(5)]
#[Backoff(5, 30, 120, 600)]
#[Timeout(30)]
#[MaxExceptions(3)]
class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Notification $notification)
    {
        $this->onQueue($this->queueName($this->notification->priority));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimitedWithRedis('channel-'.$this->notification->channel->value),
            new ThrottlesExceptionsWithRedis(5, 60),
        ];
    }

    public function handle(DeliveryService $deliveryService): void
    {
        $notification = $this->notification->fresh() ?? $this->notification;

        if (! in_array($notification->status, [NotificationStatus::Pending, NotificationStatus::Processing], true)) {
            return;
        }

        Log::shareContext([
            'correlation_id' => $notification->id,
            'channel' => $notification->channel->value,
        ]);

        Log::info('Notification job processing started.', [
            'notification_id' => $notification->id,
            'priority' => $notification->priority->value,
        ]);

        $deliveryService->deliver($notification);

        Log::info('Notification delivery completed successfully.', [
            'notification_id' => $notification->id,
        ]);
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
