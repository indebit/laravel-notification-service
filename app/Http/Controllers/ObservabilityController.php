<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HealthService;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ObservabilityController extends Controller
{
    public function __construct(
        public MetricsService $metricsService,
        public HealthService $healthService,
    ) {}

    /**
     * Get queue and delivery metrics
     *
     * Returns queue depth, notification status counts, success rates, latency,
     * and throughput for the notification processing pipeline.
     *
     * @group Observability
     *
     * @response 200 {"queue_depth":{"high":0,"default":12,"low":45},"notification_status_counts":{"pending":150,"processing":3,"delivered":8420,"failed":23,"cancelled":5},"success_rate":{"last_hour":{"total":500,"delivered":485,"failed":15,"rate":97},"last_24h":{"total":12000,"delivered":11800,"failed":200,"rate":98.33}},"latency":{"average_ms":120,"p95_ms":240,"per_channel":{"sms":95,"email":140,"push":80}},"throughput":{"per_minute":85.4,"window_minutes":5},"timestamp":"2026-04-29T14:00:00.000000Z"}
     */
    public function metrics(): JsonResponse
    {
        return response()->json($this->metricsService->getMetrics());
    }

    /**
     * Get service health status
     *
     * Checks database, Redis, and Horizon status for operational health.
     *
     * @group Observability
     *
     * @response 200 scenario="Healthy" {"status":"healthy","checks":{"database":{"status":"ok","latency_ms":2},"redis":{"status":"ok","latency_ms":1},"horizon":{"status":"running"}},"timestamp":"2026-04-29T14:00:00.000000Z"}
     * @response 503 scenario="Unhealthy" {"status":"unhealthy","checks":{"database":{"status":"failed","latency_ms":3,"error":"SQLSTATE[HY000] [2002] Connection refused"},"redis":{"status":"failed","latency_ms":2,"error":"Connection refused"},"horizon":{"status":"inactive"}},"timestamp":"2026-04-29T14:00:00.000000Z"}
     */
    public function health(): JsonResponse
    {
        $health = $this->healthService->check();
        $statusCode = $health['status'] === 'unhealthy'
            ? Response::HTTP_SERVICE_UNAVAILABLE
            : Response::HTTP_OK;

        return response()->json($health, $statusCode);
    }
}
