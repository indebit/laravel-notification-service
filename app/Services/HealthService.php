<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class HealthService
{
    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();
        $horizon = $this->checkHorizon();

        $status = 'healthy';
        if ($database['status'] !== 'ok' || $redis['status'] !== 'ok') {
            $status = 'unhealthy';
        } elseif ($horizon['status'] !== 'running') {
            $status = 'degraded';
        }

        return [
            'status' => $status,
            'checks' => [
                'database' => $database,
                'redis' => $redis,
                'horizon' => $horizon,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->getPdo();

            return [
                'status' => 'ok',
                'latency_ms' => $this->latencyMs($start),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'failed',
                'latency_ms' => $this->latencyMs($start),
                ...$this->error($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkRedis(): array
    {
        $start = microtime(true);

        try {
            Redis::ping();

            return [
                'status' => 'ok',
                'latency_ms' => $this->latencyMs($start),
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'failed',
                'latency_ms' => $this->latencyMs($start),
                ...$this->error($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkHorizon(): array
    {
        try {
            if (! app()->bound(MasterSupervisorRepository::class)) {
                return ['status' => 'inactive'];
            }

            $names = app(MasterSupervisorRepository::class)->names();

            return count($names) > 0
                ? ['status' => 'running']
                : ['status' => 'inactive'];
        } catch (\Throwable $exception) {
            return [
                'status' => 'inactive',
                ...$this->error($exception),
            ];
        }
    }

    /**
     * Connection errors can include hosts and credentials, so they are only shown in debug mode.
     *
     * @return array<string, string>
     */
    private function error(\Throwable $exception): array
    {
        return config('app.debug') ? ['error' => $exception->getMessage()] : [];
    }

    private function latencyMs(float $start): int
    {
        return max(1, (int) round((microtime(true) - $start) * 1000));
    }
}
