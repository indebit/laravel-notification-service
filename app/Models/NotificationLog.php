<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $notification_id
 * @property int $attempt_number
 * @property string $status
 * @property array<string, mixed>|null $response_body
 * @property string|null $error_message
 * @property int $latency_ms
 * @property Carbon $created_at
 */
class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'attempt_number',
        'status',
        'response_body',
        'error_message',
        'latency_ms',
        'created_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Notification, NotificationLog>
     */
    public function notification(): BelongsTo
    {
        /** @var BelongsTo<Notification, NotificationLog> $relation */
        $relation = $this->belongsTo(Notification::class);

        return $relation;
    }
}
