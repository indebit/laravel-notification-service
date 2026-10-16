<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('notifications:process-scheduled')]
#[Description('Dispatch due scheduled notifications to queue workers')]
class ProcessScheduledNotifications extends Command
{
    public function handle(): int
    {
        $dispatched = 0;

        Notification::query()
            ->where('status', NotificationStatus::Pending)
            ->whereNull('queued_at')
            ->where('scheduled_at', '<=', now())
            ->chunkById(100, function (Collection $notifications) use (&$dispatched): void {
                foreach ($notifications as $notification) {
                    // Claim the row first, so a second scheduler run cannot queue it again.
                    $claimed = Notification::query()
                        ->whereKey($notification->getKey())
                        ->whereNull('queued_at')
                        ->update(['queued_at' => now()]);

                    if ($claimed === 1) {
                        SendNotificationJob::dispatch($notification);
                        $dispatched++;
                    }
                }
            });

        $this->info("Dispatched {$dispatched} scheduled notifications.");

        return self::SUCCESS;
    }
}
