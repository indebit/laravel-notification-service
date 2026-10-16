<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case Sms = 'sms';
    case Email = 'email';
    case Push = 'push';

    /**
     * Longest content the provider accepts on this channel.
     */
    public function maxContentLength(): int
    {
        return match ($this) {
            self::Sms => 160,
            self::Email => 10000,
            self::Push => 256,
        };
    }
}
