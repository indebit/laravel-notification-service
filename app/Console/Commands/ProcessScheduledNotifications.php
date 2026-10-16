<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('notifications:process-scheduled')]
#[Description('Dispatch due scheduled notifications to queue workers')]
class ProcessScheduledNotifications extends Command
{
    public function handle(): int
    {
        $dispatched = 0;

        Notification::query()
            ->where('status', NotificationStatus::Pending->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($notifications) use (&$dispatched): void {
                foreach ($notifications as $notification) {
                    Log::info('Processing scheduled notification.', [
                        'notification_id' => $notification->id,
                        'scheduled_at' => $notification->scheduled_at,
                    ]);
                    SendNotificationJob::dispatch($notification)->onQueue($this->queueName($notification->priority));
                    $notification->forceFill(['scheduled_at' => null])->saveQuietly();
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} scheduled notifications.");

        return self::SUCCESS;
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
