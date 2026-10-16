<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\WaitTimeCalculator;

class MetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $queueDepth = $this->queueDepth();
        $notificationStatusCounts = $this->notificationStatusCounts();
        $successRateLastHour = $this->successRateForWindow(now()->subHour());
        $successRateLast24h = $this->successRateForWindow(now()->subDay());
        $latency = $this->latencyMetrics();
        $throughput = $this->throughputMetrics();

        return [
            'queue_depth' => $queueDepth,
            'notification_status_counts' => $notificationStatusCounts,
            'success_rate' => [
                'last_hour' => $successRateLastHour,
                'last_24h' => $successRateLast24h,
            ],
            'latency' => $latency,
            'throughput' => $throughput,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueDepth(): array
    {
        $pendingByPriority = Notification::query()
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->where('status', NotificationStatus::Pending->value)
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $depth = [
            'high' => (int) ($pendingByPriority[NotificationPriority::High->value] ?? 0),
            'default' => (int) ($pendingByPriority[NotificationPriority::Normal->value] ?? 0),
            'low' => (int) ($pendingByPriority[NotificationPriority::Low->value] ?? 0),
        ];

        if (app()->bound(MetricsRepository::class) && app()->bound(SupervisorRepository::class)) {
            try {
                $waitTimeCalculator = app(WaitTimeCalculator::class);
                $waitTimes = $waitTimeCalculator->calculate();
                $depth['wait_time_seconds'] = [
                    'high' => (int) ($waitTimes['redis:high'] ?? 0),
                    'default' => (int) ($waitTimes['redis:default'] ?? 0),
                    'low' => (int) ($waitTimes['redis:low'] ?? 0),
                ];
            } catch (\Throwable) {
                $depth['wait_time_seconds'] = [
                    'high' => 0,
                    'default' => 0,
                    'low' => 0,
                ];
            }
        }

        return $depth;
    }

    /**
     * @return array<string, int>
     */
    private function notificationStatusCounts(): array
    {
        $counts = Notification::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            NotificationStatus::Pending->value => (int) ($counts[NotificationStatus::Pending->value] ?? 0),
            NotificationStatus::Processing->value => (int) ($counts[NotificationStatus::Processing->value] ?? 0),
            NotificationStatus::Delivered->value => (int) ($counts[NotificationStatus::Delivered->value] ?? 0),
            NotificationStatus::Failed->value => (int) ($counts[NotificationStatus::Failed->value] ?? 0),
            NotificationStatus::Cancelled->value => (int) ($counts[NotificationStatus::Cancelled->value] ?? 0),
        ];
    }

    /**
     * @return array{total: int, delivered: int, failed: int, rate: float}
     */
    private function successRateForWindow(Carbon $from): array
    {
        $logs = NotificationLog::query()
            ->where('created_at', '>=', $from)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $delivered = (int) ($logs['accepted'] ?? 0);
        $failed = (int) ($logs['failed'] ?? 0);
        $total = $delivered + $failed;

        $rate = $total > 0 ? round(($delivered / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'rate' => $rate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function latencyMetrics(): array
    {
        $from = now()->subHour();

        $averageMs = (int) round(
            (float) NotificationLog::query()
                ->where('created_at', '>=', $from)
                ->avg('latency_ms')
        );

        $perChannel = DB::table('notification_logs')
            ->join('notifications', 'notifications.id', '=', 'notification_logs.notification_id')
            ->where('notification_logs.created_at', '>=', $from)
            ->select('notifications.channel', DB::raw('AVG(notification_logs.latency_ms) as avg_latency'))
            ->groupBy('notifications.channel')
            ->pluck('avg_latency', 'notifications.channel');

        $p95Value = $this->latencyP95Ms($from);

        return [
            'average_ms' => $averageMs,
            'p95_ms' => $p95Value,
            'per_channel' => [
                'sms' => (int) round((float) ($perChannel['sms'] ?? 0)),
                'email' => (int) round((float) ($perChannel['email'] ?? 0)),
                'push' => (int) round((float) ($perChannel['push'] ?? 0)),
            ],
        ];
    }

    /**
     * @return array{per_minute: float, window_minutes: int}
     */
    private function throughputMetrics(): array
    {
        $windowMinutes = 5;
        $processed = NotificationLog::query()
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        return [
            'per_minute' => round($processed / $windowMinutes, 2),
            'window_minutes' => $windowMinutes,
        ];
    }

    /**
     * Approximate 95th percentile latency without loading all rows into memory.
     */
    private function latencyP95Ms(Carbon $from): int
    {
        $base = NotificationLog::query()->where('created_at', '>=', $from);
        $count = (clone $base)->count();
        if ($count === 0) {
            return 0;
        }

        $offset = max(0, (int) ceil($count * 0.95) - 1);
        $value = (clone $base)->orderBy('latency_ms')->skip($offset)->value('latency_ms');

        return (int) ($value ?? 0);
    }
}
