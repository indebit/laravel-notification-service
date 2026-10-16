<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the provider rejects a delivery attempt, so the queue retries the job.
 */
class NotificationDeliveryFailed extends RuntimeException {}
