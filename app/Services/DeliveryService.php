<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationLogStatus;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Exceptions\NotificationDeliveryFailed;
use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class DeliveryService
{
    /**
     * Send one delivery attempt to the provider.
     *
     * A failed attempt is logged and the notification goes back to pending, then an exception
     * is thrown so the queue can retry it. The notification is only marked as failed once the
     * job gives up, in markFailed().
     *
     * Returns false when the notification was cancelled or finished before this attempt started.
     *
     * @throws NotificationDeliveryFailed|ConnectionException
     */
    public function deliver(Notification $notification): bool
    {
        $attemptNumber = $notification->attempt_count + 1;

        $claimed = $this->transition($notification, NotificationStatus::Processing, [
            'processing_started_at' => now(),
        ], from: [NotificationStatus::Pending, NotificationStatus::Processing]);

        if (! $claimed) {
            return false;
        }

        $start = microtime(true);

        try {
            $response = Http::connectTimeout((int) config('services.notification_provider.connect_timeout', 5))
                ->timeout((int) config('services.notification_provider.timeout', 10))
                ->post((string) config('services.notification_provider.url'), [
                    'to' => $notification->recipient,
                    'channel' => $notification->channel->value,
                    'content' => $notification->content,
                ]);
        } catch (ConnectionException $exception) {
            $this->recordFailedAttempt($notification, $attemptNumber, $this->latencyMs($start), $exception->getMessage());

            throw $exception;
        }

        $latencyMs = $this->latencyMs($start);

        if ($response->status() !== 202) {
            $error = $this->errorMessage($response);
            $this->recordFailedAttempt($notification, $attemptNumber, $latencyMs, $error, $response->json());

            throw new NotificationDeliveryFailed($error);
        }

        $messageId = $response->json('messageId');

        NotificationLog::create([
            'notification_id' => $notification->id,
            'attempt_number' => $attemptNumber,
            'status' => NotificationLogStatus::Accepted->value,
            'response_body' => $response->json(),
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);

        $this->transition($notification, NotificationStatus::Delivered, [
            'external_message_id' => is_scalar($messageId) ? (string) $messageId : null,
            'delivered_at' => now(),
            'attempt_count' => $attemptNumber,
            'last_error' => null,
        ]);

        return true;
    }

    /**
     * Mark the notification as permanently failed once the queue has run out of attempts.
     */
    public function markFailed(Notification $notification, string $reason): void
    {
        $this->transition($notification, NotificationStatus::Failed, [
            'last_error' => $reason,
            'failed_at' => now(),
        ], from: [NotificationStatus::Pending, NotificationStatus::Processing]);
    }

    /**
     * @param  array<string, mixed>|null  $responseBody
     */
    private function recordFailedAttempt(
        Notification $notification,
        int $attemptNumber,
        int $latencyMs,
        string $error,
        ?array $responseBody = null,
    ): void {
        NotificationLog::create([
            'notification_id' => $notification->id,
            'attempt_number' => $attemptNumber,
            'status' => NotificationLogStatus::Failed->value,
            'response_body' => $responseBody,
            'error_message' => $error,
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);

        $this->transition($notification, NotificationStatus::Pending, [
            'attempt_count' => $attemptNumber,
            'last_error' => $error,
        ], from: [NotificationStatus::Processing]);
    }

    /**
     * Move the notification to a new status. With $from, the update only applies while the stored
     * status is one of those, so a concurrent cancel is never overwritten.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<NotificationStatus>  $from
     */
    private function transition(Notification $notification, NotificationStatus $status, array $attributes = [], array $from = []): bool
    {
        $oldStatus = $notification->status;

        if ($from === []) {
            $notification->update([...$attributes, 'status' => $status]);
        } else {
            $updated = Notification::query()
                ->whereKey($notification->getKey())
                ->whereIn('status', $from)
                ->update([...$attributes, 'status' => $status]);

            $notification->refresh();

            if ($updated === 0) {
                return false;
            }
        }

        if ($oldStatus !== $status) {
            event(new NotificationStatusChanged($notification, $oldStatus, $status));
        }

        return true;
    }

    private function errorMessage(Response $response): string
    {
        $error = $response->json('error') ?? $response->json('message');

        if (is_string($error) && $error !== '') {
            return $error;
        }

        return "Provider returned unexpected status {$response->status()}";
    }

    private function latencyMs(float $start): int
    {
        return max(1, (int) round((microtime(true) - $start) * 1000));
    }
}
