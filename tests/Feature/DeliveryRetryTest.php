<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\travel;

uses(RefreshDatabase::class);

// Runs the job through a real worker on the database queue, so middleware, backoff and
// MaxExceptions behave as they do in production. Status broadcasts are faked so the queue
// only holds the delivery job.
beforeEach(function (): void {
    config(['queue.default' => 'database']);
    Event::fake([NotificationStatusChanged::class]);
});

function workOnce(): void
{
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--queue' => 'high,default,low']);
}

function pendingNotification(): Notification
{
    return Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000030',
        'content' => 'Retry through the worker',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);
}

it('retries a failed delivery after the backoff delay', function (): void {
    Http::fake(['*' => Http::response(['error' => 'Service unavailable'], 503)]);
    $notification = pendingNotification();

    SendNotificationJob::dispatch($notification);
    workOnce();

    expect($notification->fresh()?->status)->toBe(NotificationStatus::Pending)
        ->and($notification->fresh()?->attempt_count)->toBe(1);

    $availableAt = (int) DB::table('jobs')->value('available_at');
    expect($availableAt - now()->getTimestamp())->toBe(5);

    workOnce();
    expect($notification->fresh()?->attempt_count)->toBe(1);

    travel(5)->seconds();
    workOnce();
    expect($notification->fresh()?->attempt_count)->toBe(2);
});

it('marks the notification failed after five failed attempts', function (): void {
    Http::fake(['*' => Http::response(['error' => 'Service unavailable'], 503)]);
    $notification = pendingNotification();

    SendNotificationJob::dispatch($notification);

    foreach ([0, 5, 30, 120, 600] as $delay) {
        travel($delay)->seconds();
        workOnce();
    }

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Failed)
        ->and($notification->attempt_count)->toBe(5)
        ->and($notification->failed_at)->not->toBeNull()
        ->and($notification->logs()->count())->toBe(5);
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(1);
});

it('delivers on a retry once the provider recovers', function (): void {
    Http::fakeSequence()
        ->push(['error' => 'Service unavailable'], 503)
        ->push(['messageId' => 'recovered', 'status' => 'accepted'], 202);
    $notification = pendingNotification();

    SendNotificationJob::dispatch($notification);
    workOnce();
    travel(5)->seconds();
    workOnce();

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered)
        ->and($notification->attempt_count)->toBe(2)
        ->and(DB::table('jobs')->count())->toBe(0);
});
