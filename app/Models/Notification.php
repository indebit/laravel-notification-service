<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $batch_id
 * @property NotificationChannel $channel
 * @property string $recipient
 * @property string|null $content
 * @property Carbon|null $scheduled_at
 * @property NotificationPriority $priority
 * @property NotificationStatus $status
 * @property string|null $idempotency_key
 * @property Carbon|null $processing_started_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property int $attempt_count
 * @property string|null $last_error
 * @property string|null $external_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EloquentCollection<int, NotificationLog> $logs
 */
class Notification extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'batch_id',
        'channel',
        'recipient',
        'content',
        'scheduled_at',
        'priority',
        'status',
        'idempotency_key',
        'processing_started_at',
        'delivered_at',
        'failed_at',
        'attempt_count',
        'last_error',
        'external_message_id',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'priority' => NotificationPriority::class,
            'status' => NotificationStatus::class,
            'scheduled_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<NotificationLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeByStatus(Builder $query, NotificationStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeByChannel(Builder $query, NotificationChannel $channel): Builder
    {
        return $query->where('channel', $channel->value);
    }

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeByDateRange(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  Builder<Notification>  $query
     * @return Builder<Notification>
     */
    public function scopeByBatchId(Builder $query, ?string $batchId): Builder
    {
        if ($batchId === null) {
            return $query;
        }

        return $query->where('batch_id', $batchId);
    }
}
