<?php

declare(strict_types=1);

use App\Jobs\SendNotificationJob;
use App\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use function Pest\Laravel\getJson;
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
function templatePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'order_shipped',
        'channel' => 'sms',
        'body' => 'Hi {{name}}, order {{order_id}} is on the way!',
    ], $overrides);
}

it('lists templates with pagination metadata', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'alpha_template',
        'channel' => 'email',
        'body' => 'Hello {{name}}',
    ]);

    getJson('/api/templates')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('creates a template and returns 201 with expected shape', function (): void {
    $payload = templatePayload();

    postJson('/api/templates', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', $payload['name'])
        ->assertJsonPath('data.channel', $payload['channel'])
        ->assertJsonPath('data.body', $payload['body'])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'channel',
                'body',
                'created_at',
                'updated_at',
            ],
        ]);

    expect(NotificationTemplate::query()->where('name', $payload['name'])->exists())->toBeTrue();
});

it('validates required fields when creating a template', function (): void {
    postJson('/api/templates', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'channel', 'body']);
});

it('rejects duplicate template names', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'unique_once',
        'channel' => 'sms',
        'body' => 'Ping',
    ]);

    postJson('/api/templates', templatePayload(['name' => 'unique_once']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns a template by id', function (): void {
    $template = NotificationTemplate::query()->create([
        'name' => 'show_me',
        'channel' => 'push',
        'body' => 'Alert: {{title}}',
    ]);

    getJson("/api/templates/{$template->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $template->id)
        ->assertJsonPath('data.name', 'show_me');
});

it('returns 404 when template id does not exist', function (): void {
    getJson('/api/templates/'.Str::uuid()->toString())
        ->assertNotFound();
});

it('creates a notification using template name and variables after the template exists', function (): void {
    postJson('/api/templates', [
        'name' => 'payment_received',
        'channel' => 'sms',
        'body' => 'Hi {{name}}, order {{order_id}} total {{total}}.',
    ])->assertCreated();

    $notificationPayload = [
        'recipient' => '+40745123456789',
        'channel' => 'sms',
        'template_name' => 'payment_received',
        'content' => 'Test message here',
        'template_variables' => [
            'name' => 'Catalin',
            'order_id' => 'ORD-4567',
            'total' => 'EUR129.99',
        ],
        'priority' => 'high',
    ];

    $response = postJson('/api/notifications', $notificationPayload)->assertCreated();

    $response
        ->assertJsonPath('data.recipient', '+40745123456789')
        ->assertJsonPath('data.channel', 'sms')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.content', 'Hi Catalin, order ORD-4567 total EUR129.99.');

    Queue::assertPushed(SendNotificationJob::class);
});
