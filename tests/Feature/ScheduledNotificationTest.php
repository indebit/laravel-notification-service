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

it('does not dispatch job immediately when scheduled_at is in the future', function (): void {
    Queue::fake();

    postJson('/api/notifications', [
        'recipient' => '+40745123456',
        'channel' => NotificationChannel::Sms->value,
        'content' => 'Scheduled message',
        'scheduled_at' => now()->addHour()->toIso8601String(),
    ])->assertCreated();

    Queue::assertNotPushed(SendNotificationJob::class);
});

it('stores scheduled_at on the notification', function (): void {
    Queue::fake();
    $scheduledAt = now()->addMinutes(30)->toIso8601String();

    $response = postJson('/api/notifications', [
        'recipient' => '+40745123456',
        'channel' => NotificationChannel::Sms->value,
        'content' => 'Scheduled storage test',
        'scheduled_at' => $scheduledAt,
    ])->assertCreated();

    $id = $response->json('data.id');
    $notification = Notification::query()->findOrFail($id);

    expect($notification->scheduled_at)->not()->toBeNull();
});

it('the scheduled command dispatches jobs for notifications where scheduled_at has passed', function (): void {
    Queue::fake();

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'due@example.com',
        'content' => 'Due now',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->subMinute(),
    ]);

    artisan('notifications:process-scheduled')->assertSuccessful();

    Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($notification): bool {
        return $job->notification->is($notification);
    });
});

it('the scheduled command does not dispatch jobs for future notifications', function (): void {
    Queue::fake();

    Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'future@example.com',
        'content' => 'Future',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->addHour(),
    ]);

    artisan('notifications:process-scheduled')->assertSuccessful();

    Queue::assertNotPushed(SendNotificationJob::class);
});

it('rejects scheduled_at in the past', function (): void {
    postJson('/api/notifications', [
        'recipient' => '+40745123456',
        'channel' => NotificationChannel::Sms->value,
        'content' => 'Past schedule',
        'scheduled_at' => now()->subMinute()->toIso8601String(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['scheduled_at']);
});
