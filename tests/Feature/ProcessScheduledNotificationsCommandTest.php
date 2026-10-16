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
use function Pest\Laravel\postJson;

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

    $notification->refresh();

    expect($notification->scheduled_at)->not->toBeNull()
        ->and($notification->queued_at)->not->toBeNull();
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

it('dispatches every due notification across several chunks', function (): void {
    foreach (range(1, 230) as $i) {
        Notification::query()->create([
            'channel' => NotificationChannel::Email,
            'recipient' => "user{$i}@example.com",
            'content' => 'Due',
            'priority' => NotificationPriority::Normal,
            'status' => NotificationStatus::Pending,
            'scheduled_at' => now()->subMinutes($i),
        ]);
    }

    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertPushed(SendNotificationJob::class, 230);
    expect(Notification::query()->whereNull('queued_at')->count())->toBe(0);
});

it('queues an unscheduled notification straight away and marks it as queued', function (): void {
    $id = postJson('/api/notifications', [
        'recipient' => '+40745123456',
        'channel' => 'sms',
        'content' => 'Right now',
    ])->assertCreated()->json('data.id');

    expect(Notification::query()->find($id)?->queued_at)->not->toBeNull();
    Queue::assertPushed(SendNotificationJob::class, 1);
});

it('recovers a notification whose dispatch failed when it was created', function (): void {
    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Missed dispatch',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);
    Notification::query()->whereKey($notification->id)->update(['created_at' => now()->subMinutes(2)]);

    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertPushed(SendNotificationJob::class, 1);
    expect($notification->fresh()?->queued_at)->not->toBeNull();
});

it('leaves a fresh unqueued notification to the request that created it', function (): void {
    Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Just created',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    artisan('notifications:process-scheduled')->assertExitCode(0);

    Queue::assertNothingPushed();
});
