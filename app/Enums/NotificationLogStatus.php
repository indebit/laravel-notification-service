<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationLogStatus: string
{
    case Accepted = 'accepted';
    case Failed = 'failed';
}
