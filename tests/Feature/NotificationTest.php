<?php

declare(strict_types=1);

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
    Http::fake();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function notificationPayload(array $overrides = []): array
{
    return array_merge([
        'recipient' => '+40745123456',
        'channel' => 'sms',
        'content' => 'Test notification content',
        'priority' => 'normal',
    ], $overrides);
}

it('creates a notification and returns 201 with correct structure', function (): void {
    $response = postJson('/api/notifications', notificationPayload());

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'batch_id',
                'channel',
                'recipient',
                'content',
                'priority',
                'status',
                'idempotency_key',
                'processing_started_at',
                'delivered_at',
                'failed_at',
                'attempt_count',
                'last_error',
                'external_message_id',
                'created_at',
                'updated_at',
            ],
        ]);
});

it('validates required fields when creating a notification', function (): void {
    postJson('/api/notifications', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['recipient', 'channel', 'content']);
});

it('validates channel must be a valid enum value', function (): void {
    postJson('/api/notifications', notificationPayload(['channel' => 'fax']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['channel']);
});

it('validates sms content max length of 160 characters', function (): void {
    postJson('/api/notifications', notificationPayload([
        'channel' => 'sms',
        'content' => Str::repeat('a', 161),
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content']);
});

it('dispatches SendNotificationJob when notification is created', function (): void {
    postJson('/api/notifications', notificationPayload())->assertCreated();

    Queue::assertPushed(SendNotificationJob::class);
});

it('returns existing notification for the same idempotency key', function (): void {
    $payload = notificationPayload(['idempotency_key' => 'idem-key-1']);

    $first = postJson('/api/notifications', $payload)->assertCreated()->json('data.id');
    $second = postJson('/api/notifications', $payload)->assertCreated()->json('data.id');

    expect($second)->toBe($first);
    expect(Notification::query()->count())->toBe(1);
});

it('creates a batch and all notifications share the same batch_id', function (): void {
    $payload = [
        'notifications' => [
            notificationPayload(['recipient' => '+111111111111']),
            notificationPayload(['recipient' => '+222222222222', 'channel' => 'email']),
            notificationPayload(['recipient' => '+333333333333', 'channel' => 'push']),
        ],
    ];

    $response = postJson('/api/notifications/batch', $payload)->assertCreated();

    $batchId = $response->json('batch_id');

    expect(is_string($batchId))->toBeTrue();
    expect($batchId)->not->toBe('');
    expect(Notification::query()->where('batch_id', $batchId)->count())->toBe(3);
});

it('rejects batch with empty notifications array', function (): void {
    postJson('/api/notifications/batch', ['notifications' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['notifications']);
});

it('rejects batch exceeding 1000 notifications', function (): void {
    $notifications = array_fill(0, 1001, notificationPayload());

    postJson('/api/notifications/batch', ['notifications' => $notifications])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['notifications']);
});

it('retrieves a notification by id', function (): void {
    $id = postJson('/api/notifications', notificationPayload())->json('data.id');

    getJson("/api/notifications/{$id}")
        ->assertOk()
        ->assertJsonPath('data.id', $id);
});

it('returns 404 for non-existent notification id', function (): void {
    getJson('/api/notifications/'.Str::uuid()->toString())
        ->assertNotFound();
});

it('retrieves notifications by batch id', function (): void {
    $batchResponse = postJson('/api/notifications/batch', [
        'notifications' => [
            notificationPayload(['recipient' => '+411111111111']),
            notificationPayload(['recipient' => '+422222222222']),
        ],
    ])->assertCreated();

    $batchId = $batchResponse->json('batch_id');

    getJson("/api/notifications/batch/{$batchId}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists notifications with pagination', function (): void {
    postJson('/api/notifications/batch', [
        'notifications' => array_map(
            fn (int $i): array => notificationPayload(['recipient' => '+5000000000'.$i]),
            range(1, 20)
        ),
    ])->assertCreated();

    getJson('/api/notifications?per_page=10')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure(['links', 'meta']);
});

it('filters notifications by status', function (): void {
    Notification::query()->create([
        ...notificationPayload(['channel' => 'email']),
        'status' => NotificationStatus::Delivered,
    ]);
    Notification::query()->create([
        ...notificationPayload(['recipient' => '+611111111111']),
        'status' => NotificationStatus::Pending,
    ]);

    getJson('/api/notifications?status=delivered')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'delivered');
});

it('filters notifications by channel', function (): void {
    postJson('/api/notifications', notificationPayload(['channel' => 'email', 'recipient' => 'test@example.com']))->assertCreated();
    postJson('/api/notifications', notificationPayload(['channel' => 'sms', 'recipient' => '+711111111111']))->assertCreated();

    getJson('/api/notifications?channel=email')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.channel', 'email');
});

it('cancels a pending notification successfully', function (): void {
    $id = postJson('/api/notifications', notificationPayload())->json('data.id');

    patchJson("/api/notifications/{$id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('cannot cancel a delivered notification', function (): void {
    $notification = Notification::query()->create([
        ...notificationPayload(['channel' => 'push']),
        'status' => NotificationStatus::Delivered,
    ]);

    patchJson("/api/notifications/{$notification->id}/cancel")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['notification']);
});
