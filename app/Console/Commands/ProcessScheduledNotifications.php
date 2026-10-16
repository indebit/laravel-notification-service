<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('notifications:process-scheduled')]
#[Description('Queue scheduled notifications that are due, and any notification that missed its dispatch')]
class ProcessScheduledNotifications extends Command
{
    public function handle(): int
    {
        $dispatched = 0;

        Notification::query()
            ->where('status', NotificationStatus::Pending)
            ->whereNull('queued_at')
            ->where(fn (Builder $query) => $query
                ->where('scheduled_at', '<=', now())
                // Recovers notifications whose dispatch failed when they were created.
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('scheduled_at')
                    ->where('created_at', '<=', now()->subMinute())))
            ->chunkById(100, function (Collection $notifications) use (&$dispatched): void {
                foreach ($notifications as $notification) {
                    // Claim the row first, so a second scheduler run cannot queue it again.
                    $claimed = Notification::query()
                        ->whereKey($notification->getKey())
                        ->whereNull('queued_at')
                        ->update(['queued_at' => now()]);

                    if ($claimed === 0) {
                        continue;
                    }

                    try {
                        SendNotificationJob::dispatch($notification);
                    } catch (Throwable $exception) {
                        $notification->forceFill(['queued_at' => null])->save();

                        throw $exception;
                    }

                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} scheduled notifications.");

        return self::SUCCESS;
    }
}
