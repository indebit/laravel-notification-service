<?php

declare(strict_types=1);

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns metrics with expected structure', function (): void {
    seedMetricsData();

    getJson('/api/metrics')
        ->assertSuccessful()
        ->assertJsonStructure([
            'queue_depth' => ['high', 'default', 'low'],
            'notification_status_counts',
            'success_rate' => ['last_hour', 'last_24h'],
            'latency' => ['average_ms', 'per_channel'],
            'throughput' => ['per_minute', 'window_minutes'],
            'timestamp',
        ]);
});

it('calculates correct success and failure rates from notification logs', function (): void {
    seedMetricsData();

    $response = getJson('/api/metrics')
        ->assertSuccessful()
        ->json();

    expect($response['success_rate']['last_hour']['total'])->toBe(4);
    expect($response['success_rate']['last_hour']['delivered'])->toBe(3);
    expect($response['success_rate']['last_hour']['failed'])->toBe(1);
    expect((float) $response['success_rate']['last_hour']['rate'])->toBe(75.0);
});

it('returns per channel latency averages', function (): void {
    seedMetricsData();

    $response = getJson('/api/metrics')->assertSuccessful()->json();

    expect($response['latency']['per_channel']['sms'])->toBe(100);
    expect($response['latency']['per_channel']['email'])->toBe(200);
    expect($response['latency']['per_channel']['push'])->toBe(350);
});

it('returns throughput based on recent activity', function (): void {
    seedMetricsData();

    $response = getJson('/api/metrics')->assertSuccessful()->json();

    expect($response['throughput']['window_minutes'])->toBe(5);
    expect($response['throughput']['per_minute'])->toBe(0.8);
});

it('returns healthy status when all services are up', function (): void {
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    app()->instance(MasterSupervisorRepository::class, new StubMasterSupervisorRepository);

    getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'healthy');
});

it('returns correct structure with database redis and horizon checks', function (): void {
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    app()->instance(MasterSupervisorRepository::class, new StubMasterSupervisorRepository);

    getJson('/api/health')
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'checks' => [
                'database' => ['status', 'latency_ms'],
                'redis' => ['status', 'latency_ms'],
                'horizon' => ['status'],
            ],
            'timestamp',
        ]);
});

it('returns 200 for healthy status', function (): void {
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    app()->instance(MasterSupervisorRepository::class, new StubMasterSupervisorRepository);

    getJson('/api/health')->assertOk();
});

it('includes latency measurements for database and redis checks', function (): void {
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    app()->instance(MasterSupervisorRepository::class, new StubMasterSupervisorRepository);

    $response = getJson('/api/health')->assertOk()->json();

    expect($response['checks']['database']['latency_ms'])->toBeInt()->toBeGreaterThan(0);
    expect($response['checks']['redis']['latency_ms'])->toBeInt()->toBeGreaterThan(0);
});

it('hides connection error details outside debug mode', function (): void {
    config(['app.debug' => false]);
    Redis::shouldReceive('ping')->once()->andThrow(new RuntimeException('Connection refused to redis:6379 with password secret'));

    $response = getJson('/api/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'unhealthy')
        ->json();

    expect($response['checks']['redis']['error'])->not->toContain('secret');
});

it('counts only queued notifications in queue depth', function (): void {
    Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000020',
        'content' => 'Queued',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
        'queued_at' => now(),
    ]);

    Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000021',
        'content' => 'Scheduled for tomorrow',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
        'scheduled_at' => now()->addDay(),
    ]);

    getJson('/api/metrics')
        ->assertSuccessful()
        ->assertJsonPath('queue_depth.high', 1);
});

final class StubMasterSupervisorRepository implements MasterSupervisorRepository
{
    /**
     * @return list<string>
     */
    public function names(): array
    {
        return ['master-1'];
    }

    /**
     * @return array<int, MasterSupervisor>
     */
    public function all(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(mixed $name): array
    {
        return [];
    }

    /**
     * @param  list<string>  $names
     * @return array<string, mixed>
     */
    public function get(array $names): array
    {
        return [];
    }

    public function update(MasterSupervisor $master): void {}

    public function forget(mixed $name): void {}

    public function flushExpired(): void {}
}

function seedMetricsData(): void
{
    $sms = Notification::query()->create([
        'channel' => NotificationChannel::Sms,
        'recipient' => '+40745000001',
        'content' => 'SMS',
        'priority' => NotificationPriority::High,
        'status' => NotificationStatus::Pending,
    ]);

    $email = Notification::query()->create([
        'channel' => NotificationChannel::Email,
        'recipient' => 'email@example.com',
        'content' => 'Email',
        'priority' => NotificationPriority::Normal,
        'status' => NotificationStatus::Delivered,
    ]);

    $push = Notification::query()->create([
        'channel' => NotificationChannel::Push,
        'recipient' => 'push-user',
        'content' => 'Push',
        'priority' => NotificationPriority::Low,
        'status' => NotificationStatus::Failed,
    ]);

    NotificationLog::query()->create([
        'notification_id' => $sms->id,
        'attempt_number' => 1,
        'status' => 'accepted',
        'latency_ms' => 100,
        'created_at' => now()->subMinutes(3),
    ]);

    NotificationLog::query()->create([
        'notification_id' => $email->id,
        'attempt_number' => 1,
        'status' => 'accepted',
        'latency_ms' => 200,
        'created_at' => now()->subMinutes(2),
    ]);

    NotificationLog::query()->create([
        'notification_id' => $push->id,
        'attempt_number' => 1,
        'status' => 'accepted',
        'latency_ms' => 300,
        'created_at' => now()->subMinutes(1),
    ]);

    NotificationLog::query()->create([
        'notification_id' => $push->id,
        'attempt_number' => 2,
        'status' => 'failed',
        'latency_ms' => 400,
        'created_at' => now()->subMinutes(4),
    ]);

    NotificationLog::query()->create([
        'notification_id' => $push->id,
        'attempt_number' => 3,
        'status' => 'failed',
        'latency_ms' => 450,
        'created_at' => now()->subHours(2),
    ]);
}
