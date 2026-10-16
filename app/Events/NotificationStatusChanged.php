<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
        return [
            'notification_id' => $this->notification->id,
            'status' => $this->newStatus->value,
            'old_status' => $this->oldStatus->value,
            'channel' => $this->notification->channel->value,
            'batch_id' => $this->notification->batch_id,
            'updated_at' => $this->notification->updated_at?->toIso8601String(),
        ];
    }
}
