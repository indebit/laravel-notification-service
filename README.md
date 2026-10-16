# Notification System API

Internal HTTP API that accepts notification requests, persists them, delivers them through a configurable webhook provider asynchronously, and exposes status, metrics, and health checks.

## Overview

This service sits behind other internal backends: callers POST notifications (SMS, email, or push) and poll or subscribe for outcomes. It validates input, stores state, pushes work to Redis queues, and records delivery attempts.

MySQL holds notifications, templates, and per-attempt logs. Redis backs queues, cache, sessions, Horizon metadata, and rate limits. Laravel Horizon runs workers in Docker; Laravel Reverb handles real-time status broadcasts.

## How it works

A client hits the JSON API under `/api`. Form requests validate the payload, then controllers delegate to services. `NotificationService` writes the row (resolving idempotency or templates), then dispatches `SendNotificationJob` onto a priority queue unless `scheduled_at` defers it. Horizon workers pick up jobs, apply Redis-backed rate limits and exception throttling, then `DeliveryService` POSTs to the configured provider URL. A 202 response with a `messageId` marks the notification delivered; anything else marks failure, logs it, and may trigger retries. `NotificationStatusChanged` is broadcast on status changes for WebSocket clients.

## Tech stack

| Package / tool | Role |
| --- | --- |
| Laravel 13, PHP 8.4 | Web framework and runtime |
| MySQL | Primary data store |
| Redis | Queues, cache, Horizon, rate limiting |
| Laravel Horizon | Queue workers, metrics, `/horizon` dashboard |
| Laravel Reverb | WebSocket server for broadcasting |
| Scribe | OpenAPI + Postman artifacts, Swagger UI at `/docs` |
| Pest | Feature and smoke tests |
| PHPStan + Larastan | Static analysis at level 6 |
| Laravel Pint | Code style |
| Docker Compose | App, Nginx, MySQL, Redis, Horizon, Reverb |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 4.x or newer (includes Docker Compose v2)
- Git
- No local PHP or Composer installation required — everything runs inside containers

## Getting started

### 1. Clone the repository

```bash
git clone https://github.com/indebit/notification-system notification-api-test
cd notification-api-test
```

### 2. Configure a webhook provider URL

The application delivers notifications by POSTing to an external webhook URL. For local testing, create a free inspector at [webhook.site](https://webhook.site) and configure it to respond with **HTTP 202** and this body:

```json
{
  "messageId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "accepted",
  "timestamp": "2026-05-01T12:00:00Z"
}
```

Then set `NOTIFICATION_PROVIDER_URL` in `docker-compose.yml` under both the `app` and `horizon` environment blocks:

```yaml
NOTIFICATION_PROVIDER_URL: https://webhook.site/your-unique-uuid
```

### 3. Start the stack

```bash
docker compose up -d --build
```

On first start, Docker builds the PHP images, waits for MySQL and Redis to become healthy, then the entrypoint installs Composer dependencies (serialized across containers via a shared file lock), generates `APP_KEY`, runs migrations, and generates Scribe API docs. On a fresh clone this can take 1–3 minutes.

### 4. Verify all services are up

```bash
docker compose ps
```

All six services should show `Up` with no restarts. Follow live bootstrap output with:

```bash
docker compose logs -f app
```

### 5. Service URLs

| URL | Description |
| --- | --- |
| http://localhost:8000 | API root (Nginx → PHP-FPM) |
| http://localhost:8000/docs | Interactive Swagger UI (Scribe) |
| http://localhost:8000/horizon | Laravel Horizon queue dashboard |
| http://localhost:8000/api/health | Health check (database + Redis + Horizon) |
| http://localhost:8000/api/metrics | Delivery metrics |
| http://localhost:8080 | Reverb WebSocket server |

### 6. Run the test suite

```bash
docker compose exec app php artisan test
```

## API endpoints

Full request and response examples are in the interactive docs at `/docs` and in `docs/notification-service.postman_collection.json`.

### Notifications

| Method | Path | Description |
| --- | --- | --- |
| POST | /api/notifications | Create a notification (supports `idempotency_key`, `scheduled_at`, `template_name`) |
| POST | /api/notifications/batch | Create up to 1000 notifications sharing a `batch_id` |
| GET | /api/notifications | Paginated list with filters (status, channel, `batch_id`, date range) |
| GET | /api/notifications/{notification} | Single notification including delivery logs |
| GET | /api/notifications/batch/{batchId} | All notifications in a batch |
| PATCH | /api/notifications/{notification}/cancel | Cancel while still pending |

### Observability

| Method | Path | Description |
| --- | --- | --- |
| GET | /api/health | Database, Redis, and Horizon supervisor checks |
| GET | /api/metrics | Status histogram, success rates, latency (avg + p95), throughput |

### Templates

| Method | Path | Description |
| --- | --- | --- |
| POST | /api/templates | Create a named template with `{{placeholder}}` bodies |
| GET | /api/templates | Paginated template list |
| GET | /api/templates/{template} | Fetch one template |

### WebSocket testing

| Method | Path | Description |
| --- | --- | --- |
| GET | /websocket-test | Browser test page for connecting to Reverb channels |
| POST | /api/test/broadcast | Emit a sample `NotificationStatusChanged` event |

## Architecture decisions

Controllers stay thin by delegating to `NotificationService`, `DeliveryService`, `TemplateService`, and `MetricsService`. The codebase uses default Laravel folders — the problem is a single bounded context so a heavier DDD layout adds friction without buying separation.

Channel, priority, status, and log outcomes are PHP backed enums persisted as strings, giving exhaustiveness checks without MySQL enum migrations.

Notification primary keys are UUIDs (`HasUuids`), avoiding sequential IDs leaking volume information.

Each delivery attempt is a row in `notification_logs` instead of a JSON blob, keeping logs queryable for metrics, p95 latency, and auditing.

Per-channel throughput is enforced with `RateLimitedWithRedis` on the job, backed by named limiters in `AppServiceProvider`. The cap applies at dequeue time so the HTTP layer stays unaware of Redis token buckets.

`ThrottlesExceptionsWithRedis` backs off when the provider throws repeatedly, acting as a circuit-breaker alongside `#[Backoff(5, 30, 120, 600)]` and `#[MaxExceptions(3)]`.

`CorrelationIdMiddleware` accepts or generates `X-Correlation-ID`, pushes it into `Log::shareContext()`, and echoes it on the response for log pipeline correlation.

## Delivery and retry logic

When the provider returns **202** with a `messageId`, the notification moves to `delivered` and fires `NotificationStatusChanged`.

Non-202 responses or transport errors mark the notification `failed` and re-throw so the queue can retry. Jobs allow up to **five** attempts with delays of **5s, 30s, 2 min, and 10 min**.

Cancelled notifications are checked at dequeue time — the job exits without calling the provider if the status is no longer `pending` or `processing`.

`idempotency_key` is unique in the database. A repeat POST returns the original notification without dispatching duplicate work.

## Observability

Logs use `JsonFormatter` for structured output. `GET /api/health` returns **503** if any dependency is unhealthy. `GET /api/metrics` aggregates pending counts by priority, status histogram, rolling p95 latency, and throughput.

## Bonus features

- Scheduled send via `scheduled_at` and the `notifications:process-scheduled` Artisan command on the every-minute scheduler.
- Template CRUD with `{{variable}}` substitution when creating notifications.
- GitHub Actions workflow with parallel test and code-quality (Pint + PHPStan) jobs.
- Reverb broadcasting on `notifications.{notification_id}` and `notifications.{batch_id}` channels.
- Swagger UI at `/docs` backed by Scribe-generated OpenAPI and Postman artifacts.

## Testing

28 Pest tests covering notification CRUD, idempotency, batch limits, filters, cancellation, template APIs, and scheduled dispatch. Queue and HTTP clients are faked so tests stay fast and deterministic.

```bash
docker compose exec app php artisan test
```

## Environment variables

| Variable | Purpose |
| --- | --- |
| `DB_*` | MySQL connection |
| `REDIS_*` | Cache, queue, Horizon, rate limiters |
| `QUEUE_CONNECTION` | Must be `redis` for Horizon |
| `NOTIFICATION_PROVIDER_URL` | Webhook endpoint for outbound delivery |
| `NOTIFICATION_PROVIDER_TIMEOUT` / `NOTIFICATION_PROVIDER_CONNECT_TIMEOUT` | HTTP client timeouts (`config/services.php`) |
| `REVERB_APP_*`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Server-side Reverb config |
| `VITE_REVERB_*` | Browser-facing Echo client config |

## Project structure

```
app/
├── Console/Commands/ProcessScheduledNotifications.php
├── Enums/
├── Events/NotificationStatusChanged.php
├── Http/
│   ├── Controllers/NotificationController.php, ObservabilityController.php, TemplateController.php
│   ├── Middleware/CorrelationIdMiddleware.php
│   ├── Requests/
│   └── Resources/
├── Jobs/SendNotificationJob.php
├── Models/Notification.php, NotificationLog.php, NotificationTemplate.php
└── Services/DeliveryService.php, HealthService.php, MetricsService.php, NotificationService.php, TemplateService.php
routes/api.php
tests/Feature/NotificationTest.php, TemplateControllerTest.php, ProcessScheduledNotificationsCommandTest.php
```
