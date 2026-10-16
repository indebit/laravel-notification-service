<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Exceptions\NotificationDeliveryFailed;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Services\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

it('successfully delivers a notification when provider returns 202', function (): void {
    Http::fake([
        '*' => Http::response([
            'messageId' => 'test-uuid',
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745123456',
        'content' => 'Hello SMS',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered);
});

it('records a notification log entry on successful delivery', function (): void {
    Http::fake([
        '*' => Http::response([
            'messageId' => 'success-log-id',
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'success@example.com',
        'content' => 'Success Email',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);

    $log = NotificationLog::query()->where('notification_id', $notification->id)->first();

    expect($log)->not()->toBeNull();
    expect($log?->status)->toBe('accepted');
});

it('marks notification as delivered with external message id and delivered at', function (): void {
    Http::fake([
        '*' => Http::response([
            'messageId' => 'provider-message-123',
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Push,
        'recipient' => 'push-user',
        'content' => 'Push content',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);
    $notification->refresh();

    expect($notification->external_message_id)->toBe('provider-message-123');
    expect($notification->delivered_at)->not()->toBeNull();
});

it('handles provider failure and records failed log', function (): void {
    Http::fake([
        '*' => Http::response(['error' => 'Service unavailable'], 503),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'failed@example.com',
        'content' => 'Failed content',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    expect(fn () => app(DeliveryService::class)->deliver($notification))
        ->toThrow(NotificationDeliveryFailed::class);

    $log = NotificationLog::query()->where('notification_id', $notification->id)->latest('id')->first();
    expect($log?->status)->toBe('failed');
});

it('increments attempt count on failure', function (): void {
    Http::fake([
        '*' => Http::response(['error' => 'Service unavailable'], 503),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000000',
        'content' => 'Retry me',
        'priority' => NotificationPriority::Low,
        'status' => NotificationStatus::Pending,
        'attempt_count' => 2,
    ]);

    expect(fn () => app(DeliveryService::class)->deliver($notification))
        ->toThrow(NotificationDeliveryFailed::class);

    $notification->refresh();
    expect($notification->attempt_count)->toBe(3);
});

it('stores last error on failure', function (): void {
    Http::fake([
        '*' => Http::response(['error' => 'Provider down'], 503),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'error@example.com',
        'content' => 'Error content',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    expect(fn () => app(DeliveryService::class)->deliver($notification))
        ->toThrow(NotificationDeliveryFailed::class);

    $notification->refresh();
    expect($notification->last_error)->toContain('Provider down');
});

it('skips cancelled notifications without throwing', function (): void {
    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Push,
        'recipient' => 'cancelled-user',
        'content' => 'Cancelled',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Cancelled,
    ]);

    $job = new SendNotificationJob($notification);

    expect(fn () => $job->handle(app(DeliveryService::class)))->not()->toThrow(Throwable::class);
    assertDatabaseCount('notification_logs', 0);
});

it('calculates and stores latency ms in the log', function (): void {
    Http::fake([
        '*' => Http::response([
            'messageId' => 'latency-id',
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000000',
        'content' => 'Latency check',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);

    $log = NotificationLog::query()->where('notification_id', $notification->id)->latest('id')->first();
    expect($log?->latency_ms)->toBeInt()->toBeGreaterThan(0);
});

it('sends correct payload format to provider', function (): void {
    Http::fake([
        '*' => Http::response([
            'messageId' => 'payload-id',
            'status' => 'accepted',
            'timestamp' => now()->toIso8601String(),
        ], 202),
    ]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'payload@example.com',
        'content' => 'Payload content',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);

    Http::assertSent(function (Request $request) use ($notification): bool {
        $payload = $request->data();

        return $payload['to'] === $notification->recipient
            && $payload['channel'] === $notification->channel->value
            && $payload['content'] === $notification->content;
    });
});

it('keeps the notification pending between failed attempts so the queue can retry it', function (): void {
    Http::fake(['*' => Http::response(['error' => 'Service unavailable'], 503)]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000003',
        'content' => 'Retry me',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    expect(fn () => app(DeliveryService::class)->deliver($notification))
        ->toThrow(NotificationDeliveryFailed::class, 'Service unavailable');

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Pending)
        ->and($notification->attempt_count)->toBe(1)
        ->and($notification->failed_at)->toBeNull();
    assertDatabaseCount('notification_logs', 1);
});

it('delivers on a later attempt after the provider recovers', function (): void {
    Http::fakeSequence()
        ->push(['error' => 'Service unavailable'], 503)
        ->push(['messageId' => 'second-try', 'status' => 'accepted'], 202);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'retry@example.com',
        'content' => 'Second time lucky',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    $job = new SendNotificationJob($notification);

    expect(fn () => $job->handle(app(DeliveryService::class)))->toThrow(NotificationDeliveryFailed::class);

    (new SendNotificationJob($notification->fresh()))->handle(app(DeliveryService::class));

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Delivered)
        ->and($notification->attempt_count)->toBe(2)
        ->and($notification->last_error)->toBeNull()
        ->and($notification->external_message_id)->toBe('second-try');
    expect($notification->logs()->pluck('status')->all())->toBe(['failed', 'accepted']);
});

it('marks the notification as failed once the job gives up', function (): void {
    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Push,
        'recipient' => 'device-token',
        'content' => 'Never arrives',
        'priority' => NotificationPriority::Low,
        'status' => NotificationStatus::Pending,
        'attempt_count' => 5,
    ]);

    (new SendNotificationJob($notification))->failed(new NotificationDeliveryFailed('Provider down'));

    $notification->refresh();

    expect($notification->status)->toBe(NotificationStatus::Failed)
        ->and($notification->failed_at)->not->toBeNull()
        ->and($notification->last_error)->toBe('Provider down');
});

it('stores no external message id when the provider omits it', function (): void {
    Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

    $notification = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000004',
        'content' => 'No id',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Pending,
    ]);

    app(DeliveryService::class)->deliver($notification);

    expect($notification->fresh()?->external_message_id)->toBeNull();
});
