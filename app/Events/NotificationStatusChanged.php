<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class NotificationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public NotificationStatus $oldStatus,
        public NotificationStatus $newStatus,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $target = $this->notification->batch_id ?? $this->notification->id;

        return [
            new Channel("notifications.{$target}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $notification = Notification::query()->find($this->notification->getKey()) ?? $this->notification;

        $rawChannel = $notification->getRawOriginal('channel');
        if (! is_string($rawChannel) || $rawChannel === '') {
            throw new RuntimeException('Notification must have a persisted channel before broadcast.');
        }

        return [
            'notification_id' => $notification->id,
            'status' => $this->newStatus->value,
            'old_status' => $this->oldStatus->value,
            'channel' => NotificationChannel::from($rawChannel)->value,
            'batch_id' => $notification->batch_id,
            'updated_at' => $notification->updated_at?->toIso8601String() ?? '',
        ];
    }
}
