<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationLogStatus;
use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DeliveryService
{
    public function deliver(Notification $notification): void
    {
        $notification = $notification->fresh() ?? $notification;
        $attemptNumber = $notification->attempt_count + 1;

        $oldStatus = $notification->status;
        $notification->update([
            'status' => NotificationStatus::Processing,
            'processing_started_at' => now(),
        ]);
        event(new NotificationStatusChanged($notification, $oldStatus, NotificationStatus::Processing));

        $start = microtime(true);

        try {
            $response = Http::connectTimeout((int) config('services.notification_provider.connect_timeout', 5))
                ->timeout((int) config('services.notification_provider.timeout', 10))
                ->post((string) config('services.notification_provider.url'), [
                    'to' => $notification->recipient,
                    'channel' => $notification->channel->value,
                    'content' => $notification->content,
                ]);

            $latencyMs = $this->latencyMs($start);

            if ($response->status() !== 202) {
                $this->handleFailure($notification, $response, $attemptNumber, $latencyMs);
            }

            $oldStatus = $notification->status;
            $notification->update([
                'status' => NotificationStatus::Delivered,
                'external_message_id' => (string) $response->json('messageId'),
                'delivered_at' => now(),
                'attempt_count' => $attemptNumber,
                'last_error' => null,
            ]);

            NotificationLog::create([
                'notification_id' => $notification->id,
                'attempt_number' => $attemptNumber,
                'status' => NotificationLogStatus::Accepted->value,
                'response_body' => $response->json(),
                'latency_ms' => $latencyMs,
                'created_at' => now(),
            ]);

            event(new NotificationStatusChanged($notification, $oldStatus, NotificationStatus::Delivered));
        } catch (Throwable $exception) {
            $latencyMs = $this->latencyMs($start);
            $this->handleThrowableFailure($notification, $exception, $attemptNumber, $latencyMs);

            throw $exception;
        }
    }

    private function handleFailure(Notification $notification, Response $response, int $attemptNumber, int $latencyMs): never
    {
        $errorMessage = $response->json('error')
            ?? $response->json('message')
            ?? "Provider returned unexpected status {$response->status()}";
        $errorText = $this->stringifyError($errorMessage);

        $oldStatus = $notification->status;
        $notification->update([
            'status' => NotificationStatus::Failed,
            'attempt_count' => $attemptNumber,
            'last_error' => $errorText,
            'failed_at' => now(),
        ]);

        NotificationLog::create([
            'notification_id' => $notification->id,
            'attempt_number' => $attemptNumber,
            'status' => NotificationLogStatus::Failed->value,
            'response_body' => $response->json(),
            'error_message' => $errorText,
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);

        event(new NotificationStatusChanged($notification, $oldStatus, NotificationStatus::Failed));

        throw new RuntimeException($errorText);
    }

    private function handleThrowableFailure(Notification $notification, Throwable $exception, int $attemptNumber, int $latencyMs): void
    {
        $oldStatus = $notification->status;
        $notification->update([
            'status' => NotificationStatus::Failed,
            'attempt_count' => $attemptNumber,
            'last_error' => $exception->getMessage(),
            'failed_at' => now(),
        ]);

        NotificationLog::create([
            'notification_id' => $notification->id,
            'attempt_number' => $attemptNumber,
            'status' => NotificationLogStatus::Failed->value,
            'error_message' => $exception->getMessage(),
            'latency_ms' => $latencyMs,
            'created_at' => now(),
        ]);

        event(new NotificationStatusChanged($notification, $oldStatus, NotificationStatus::Failed));
    }

    /**
     * Calculate the latency in milliseconds.
     */
    private function latencyMs(float $start): int
    {
        return max(1, (int) round((microtime(true) - $start) * 1000));
    }

    /**
     * Convert provider error payloads to safe string values.
     */
    private function stringifyError(mixed $error): string
    {
        if (is_string($error) || is_numeric($error) || is_bool($error)) {
            return (string) $error;
        }

        if ($error === null) {
            return 'Unknown provider error';
        }

        $json = json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : 'Unserializable provider error';
    }
}
