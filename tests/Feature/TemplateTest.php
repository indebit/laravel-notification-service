<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('renders a template with variables correctly', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'order_shipped',
        'channel' => NotificationChannel::Sms,
        'body' => 'Hi {{name}}, your order {{order_id}} has shipped.',
    ]);

    $rendered = app(TemplateService::class)->render('order_shipped', [
        'name' => 'Alex',
        'order_id' => 'A-001',
    ], NotificationChannel::Sms);

    expect($rendered)->toBe('Hi Alex, your order A-001 has shipped.');
});

it('throws exception when template not found', function (): void {
    expect(fn () => app(TemplateService::class)->render('missing', [], NotificationChannel::Email))
        ->toThrow(ValidationException::class);
});

it('throws exception when required variables are missing', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'welcome_email',
        'channel' => NotificationChannel::Email,
        'body' => 'Welcome {{name}}, visit {{dashboard_url}}.',
    ]);

    expect(fn () => app(TemplateService::class)->render('welcome_email', ['name' => 'Sam'], NotificationChannel::Email))
        ->toThrow(ValidationException::class);
});

it('creates a notification using a template', function (): void {
    Queue::fake();

    NotificationTemplate::query()->create([
        'name' => 'flash_sale',
        'channel' => NotificationChannel::Push,
        'body' => '{{discount}}% OFF on {{product_name}}!',
    ]);

    $response = postJson('/api/notifications', [
        'recipient' => 'device-token-1',
        'channel' => NotificationChannel::Push->value,
        'template_name' => 'flash_sale',
        'template_variables' => [
            'discount' => '30',
            'product_name' => 'Smart Watch',
        ],
    ])->assertCreated();

    expect($response->json('data.content'))->toBe('30% OFF on Smart Watch!');
    Queue::assertPushed(SendNotificationJob::class);
});

it('allows content or template name', function (): void {
    Queue::fake();

    NotificationTemplate::query()->create([
        'name' => 'sms_generic',
        'channel' => NotificationChannel::Sms,
        'body' => 'Hello {{name}}',
    ]);

    postJson('/api/notifications', [
        'recipient' => '+40745111111',
        'channel' => NotificationChannel::Sms->value,
        'content' => 'Direct content',
    ])->assertCreated();

    postJson('/api/notifications', [
        'recipient' => '+40745222222',
        'channel' => NotificationChannel::Sms->value,
        'template_name' => 'sms_generic',
        'template_variables' => ['name' => 'Ana'],
    ])->assertCreated();
});

it('rejects request with template name but no template variables', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'welcome_push',
        'channel' => NotificationChannel::Push,
        'body' => 'Hi {{name}}',
    ]);

    postJson('/api/notifications', [
        'recipient' => 'device-token-2',
        'channel' => NotificationChannel::Push->value,
        'template_name' => 'welcome_push',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['template_variables']);
});

it('returns 422 when the template exists only for another channel', function (): void {
    Queue::fake();

    NotificationTemplate::query()->create([
        'name' => 'order_ready',
        'channel' => NotificationChannel::Email,
        'body' => 'Order {{order_id}} is ready.',
    ]);

    postJson('/api/notifications', [
        'recipient' => '+40745333333',
        'channel' => NotificationChannel::Sms->value,
        'template_name' => 'order_ready',
        'template_variables' => ['order_id' => 'A-100'],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['template_name']);

    Queue::assertNothingPushed();
});

it('returns 422 when a template variable is missing', function (): void {
    NotificationTemplate::query()->create([
        'name' => 'reset_code',
        'channel' => NotificationChannel::Sms,
        'body' => 'Your code is {{code}}, valid for {{minutes}} minutes.',
    ]);

    postJson('/api/notifications', [
        'recipient' => '+40745444444',
        'channel' => NotificationChannel::Sms->value,
        'template_name' => 'reset_code',
        'template_variables' => ['code' => '1234'],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['template_variables']);
});

it('rejects the whole batch when one template fails to render', function (): void {
    Queue::fake();

    NotificationTemplate::query()->create([
        'name' => 'welcome',
        'channel' => NotificationChannel::Email,
        'body' => 'Welcome, {{name}}.',
    ]);

    postJson('/api/notifications/batch', [
        'notifications' => [
            ['recipient' => 'a@example.com', 'channel' => 'email', 'content' => 'Plain message'],
            ['recipient' => 'b@example.com', 'channel' => 'email', 'template_name' => 'welcome', 'template_variables' => []],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['notifications.1.template_variables']);

    expect(Notification::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('validates template fields per batch item', function (): void {
    postJson('/api/notifications/batch', [
        'notifications' => [
            ['recipient' => 'a@example.com', 'channel' => 'email', 'template_name' => 'welcome'],
            ['recipient' => 'b@example.com', 'channel' => 'email'],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['notifications.0.template_variables', 'notifications.1.content']);
});
