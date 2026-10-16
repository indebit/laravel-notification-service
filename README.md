# Laravel Notification Service

HTTP API that accepts notification requests, stores them, delivers them through a configurable webhook provider in the background, and exposes status, metrics and health checks.

## Overview

The service sits behind other backends: callers POST notifications (SMS, email or push) and either poll for the outcome or subscribe to status changes over WebSockets. It validates input, stores state, queues work on Redis by priority, retries failed deliveries and records every attempt.

MySQL holds notifications, templates and per-attempt logs. Redis backs queues, cache, Horizon metadata and rate limits. Laravel Horizon runs the workers and Laravel Reverb broadcasts status changes.

## How it works

A client calls the JSON API under `/api`. Form requests validate the payload and controllers delegate to services. `NotificationService` stores the row (rendering a template if one is given), then dispatches `SendNotificationJob` to the queue for its priority, unless `scheduled_at` defers it. Horizon workers pick up the job, apply a Redis-backed rate limit per channel and exception throttling, and `DeliveryService` POSTs to the provider. A 202 response marks the notification delivered. Any other response, or a connection error, is logged as a failed attempt and the job is retried. `NotificationStatusChanged` is broadcast on every status change.

## Tech stack

| Package / tool | Role |
| --- | --- |
| Laravel 13, PHP 8.4 | Framework and runtime |
| MySQL | Primary data store |
| Redis | Queues, cache, Horizon, rate limiting |
| Laravel Horizon | Queue workers and the `/horizon` dashboard |
| Laravel Reverb | WebSocket server for status broadcasts |
| Scribe | OpenAPI spec and Postman collection, Swagger UI at `/docs` |
| Pest | Feature tests |
| PHPStan + Larastan | Static analysis at level 6 |
| Laravel Pint | Code style |
| Docker Compose | App, Nginx, MySQL, Redis, Horizon, Reverb |

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 4.x or newer (includes Docker Compose v2)
- Git

Everything runs in containers, so no local PHP or Composer is needed.

## Getting started

### 1. Clone the repository

```bash
git clone https://github.com/indebit/laravel-notification-service.git
cd laravel-notification-service
```

### 2. Configure a webhook provider URL

Notifications are delivered by POSTing to an external webhook. For local testing, create a free inspector at [webhook.site](https://webhook.site) and make it respond with **HTTP 202** and this body:

```json
{
  "messageId": "550e8400-e29b-41d4-a716-446655440000",
  "status": "accepted",
  "timestamp": "2026-05-01T12:00:00Z"
}
```

Then set `NOTIFICATION_PROVIDER_URL` in `docker-compose.yml`, in both the `app` and `horizon` environment blocks:

```yaml
NOTIFICATION_PROVIDER_URL: https://webhook.site/your-unique-uuid
```

### 3. Start the stack

```bash
docker compose up -d --build
```

On first start Docker builds the PHP images and waits for MySQL and Redis to be healthy. The entrypoint then installs Composer dependencies (one container at a time, through a shared file lock), generates `APP_KEY`, runs migrations and generates the API docs. On a fresh clone this takes 1 to 3 minutes.

### 4. Check that all services are up

```bash
docker compose ps
docker compose logs -f app
```

All six services should show `Up` with no restarts.

### 5. Service URLs

| URL | Description |
| --- | --- |
| http://localhost:8000/docs | Interactive API docs (Swagger UI) |
| http://localhost:8000/horizon | Horizon queue dashboard |
| http://localhost:8000/api/health | Health check (database, Redis, Horizon) |
| http://localhost:8000/api/metrics | Delivery metrics |
| http://localhost:8080 | Reverb WebSocket server |

### 6. Run the checks

```bash
docker compose exec app php artisan test
docker compose exec app vendor/bin/phpstan analyse
docker compose exec app vendor/bin/pint --test
```

## API endpoints

Request and response examples are in the interactive docs at `/docs` and in `docs/notification-service.postman_collection.json`.

### Notifications

| Method | Path | Description |
| --- | --- | --- |
| POST | /api/notifications | Create a notification (supports `idempotency_key`, `scheduled_at`, `template_name`) |
| POST | /api/notifications/batch | Create up to 1000 notifications sharing a `batch_id`, all or nothing |
| GET | /api/notifications | Paginated list with filters (status, channel, `batch_id`, date range) |
| GET | /api/notifications/{notification} | One notification with its delivery attempts |
| GET | /api/notifications/batch/{batchId} | All notifications in a batch |
| PATCH | /api/notifications/{notification}/cancel | Cancel while still pending |

### Templates

| Method | Path | Description |
| --- | --- | --- |
| POST | /api/templates | Create a named template per channel with `{{placeholder}}` variables |
| GET | /api/templates | Paginated template list |
| GET | /api/templates/{template} | One template |

### Observability

| Method | Path | Description |
| --- | --- | --- |
| GET | /api/health | Database, Redis and Horizon checks, 503 when a dependency is down |
| GET | /api/metrics | Queue depth, status counts, attempt success rate, latency (average and p95), throughput |

### Local development helpers

Only registered when `APP_ENV=local`:

| Method | Path | Description |
| --- | --- | --- |
| GET | /websocket-test | Browser page that subscribes to Reverb channels |
| POST | /api/test/broadcast | Broadcasts a sample `NotificationStatusChanged` event |

## Delivery and retries

- A **202** response marks the notification `delivered` and stores the provider's `messageId`.
- Any other response, or a connection error, is logged as a failed attempt in `notification_logs`. The notification goes back to `pending` with `last_error` and `attempt_count` updated, and the job is retried after **5s, 30s, 2 min and 10 min**.
- After **five** failed attempts, or one hour, the job's `failed()` hook marks the notification `failed`.
- Rate limit releases do not count as attempts: the job is bounded by `retryUntil()` and `#[MaxExceptions(5)]` rather than a fixed number of tries.
- A job for a notification that is no longer `pending` or `processing` (for example cancelled) exits without calling the provider.

## Idempotency, batches and scheduling

- `idempotency_key` is unique in the database. Creation goes through `createOrFirst()`, so a repeated or concurrent request with the same key gets the stored notification back with **200** and nothing is queued twice.
- A batch is stored in one transaction. If any item fails validation or template rendering, the response is 422 with errors keyed by item (`notifications.3.template_variables`) and nothing is created or queued.
- A template only applies to its own channel. A template that is missing for the channel, or a missing placeholder value, returns 422.
- Scheduled notifications are picked up every minute by `notifications:process-scheduled`. Each row is claimed with a conditional update on `queued_at` before its job is dispatched, and the command runs without overlapping on one server, so a notification is never queued twice. `scheduled_at` is kept as sent.

## Architecture decisions

Controllers stay thin and delegate to `NotificationService`, `DeliveryService`, `TemplateService`, `MetricsService` and `HealthService`. The project keeps the default Laravel folders: it is one bounded context, so a heavier layered layout would add friction without real separation.

Channel, priority, status and log outcome are backed enums stored as strings. Channel-specific rules (content length) and priority-specific rules (queue name) live on the enums.

Notification keys are UUIDs (`HasUuids`), so IDs do not reveal volume.

Each delivery attempt is its own row in `notification_logs`, which keeps attempts queryable for metrics, p95 latency and auditing.

Per-channel throughput is limited with `RateLimitedWithRedis` on the job, using one named limiter keyed by channel. `ThrottlesExceptionsWithRedis` backs off when the provider keeps failing.

`CorrelationIdMiddleware` accepts or generates `X-Correlation-ID`, adds it to the log context and returns it on the response.

## Scope

This is a service meant to run behind other backends on a private network. It has no authentication, and broadcast channels are public. Before exposing it, add API authentication (for example Sanctum tokens) and private broadcast channels. The health check only includes raw connection errors when `APP_DEBUG` is on.

## Testing

72 Pest tests cover creation, idempotency, batches, validation, templates, delivery and retries, scheduling, cancellation, metrics and health. Queue and HTTP calls are faked, so the suite runs in a few seconds on SQLite in memory.

```bash
docker compose exec app php artisan test
```

GitHub Actions runs the tests against MySQL and Redis, plus Pint and PHPStan, on every push.

## Environment variables

| Variable | Purpose |
| --- | --- |
| `DB_*` | MySQL connection |
| `REDIS_*` | Cache, queue, Horizon, rate limiters |
| `QUEUE_CONNECTION` | Must be `redis` for Horizon |
| `NOTIFICATION_PROVIDER_URL` | Webhook endpoint for outbound delivery |
| `NOTIFICATION_PROVIDER_TIMEOUT` / `NOTIFICATION_PROVIDER_CONNECT_TIMEOUT` | HTTP client timeouts (`config/services.php`) |
| `REVERB_APP_*`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Reverb server config |
| `VITE_REVERB_*` | Browser Echo client config |

## Project structure

```
app/
├── Console/Commands/ProcessScheduledNotifications.php
├── Enums/
├── Events/NotificationStatusChanged.php
├── Exceptions/NotificationDeliveryFailed.php
├── Http/
│   ├── Controllers/NotificationController.php, ObservabilityController.php, TemplateController.php
│   ├── Middleware/CorrelationIdMiddleware.php
│   ├── Requests/
│   └── Resources/
├── Jobs/SendNotificationJob.php
├── Models/Notification.php, NotificationLog.php, NotificationTemplate.php
└── Services/DeliveryService.php, HealthService.php, MetricsService.php, NotificationService.php, TemplateService.php
tests/Feature/
```

## License

MIT
