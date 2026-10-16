<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
});

it('dispatches SendNotificationJob for pending notifications whose scheduled_at is in the past', function (): void {
    $notification = Notification::query()->create([
        'batch_id' => null,
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Due scheduled message',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->subMinute(),
    ]);

    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($notification): bool {
        return $job->notification->id === $notification->id;
    });

    expect($notification->fresh()->scheduled_at)->toBeNull();
});

it('does not enqueue duplicate jobs when the scheduler runs again before the worker processes the notification', function (): void {
    $notification = Notification::query()->create([
        'batch_id' => null,
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Due scheduled message',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->subMinute(),
    ]);

    artisan('notifications:process-scheduled')->assertExitCode(0);
    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertPushed(SendNotificationJob::class, 1);
    Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($notification): bool {
        return $job->notification->id === $notification->id;
    });
});

it('does not dispatch jobs for future scheduled_at or non-pending scheduled notifications', function (): void {
    Notification::query()->create([
        'batch_id' => null,
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Future',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->addHour(),
    ]);

    Notification::query()->create([
        'batch_id' => null,
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123468',
        'content' => 'Delivered but was scheduled',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Delivered,
        'scheduled_at' => now()->subMinute(),
    ]);

    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertNothingPushed();
});
