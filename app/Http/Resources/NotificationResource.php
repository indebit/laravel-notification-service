<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'channel' => $this->channel->value,
            'recipient' => $this->recipient,
            'content' => $this->content,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'idempotency_key' => $this->idempotency_key,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'processing_started_at' => $this->processing_started_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'attempt_count' => $this->attempt_count,
            'last_error' => $this->last_error,
            'external_message_id' => $this->external_message_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'logs' => $this->whenLoaded('logs', function (): array {
                return $this->logs
                    ->map(fn ($log): array => [
                        'id' => $log->id,
                        'notification_id' => $log->notification_id,
                        'attempt_number' => $log->attempt_number,
                        'status' => $log->status,
                        'response_body' => $log->response_body,
                        'error_message' => $log->error_message,
                        'latency_ms' => $log->latency_ms,
                        'created_at' => $log->created_at->toIso8601String(),
                    ])
                    ->all();
            }),
        ];
    }
}
