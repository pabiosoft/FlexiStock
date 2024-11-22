<?php

namespace App\Enum;

class PaymentStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const SUCCESSFUL = 'successful';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';

    /**
     * Get all valid payment statuses.
     */
    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::SUCCESSFUL,
            self::FAILED,
            self::REFUNDED,
        ];
    }

    /**
     * Check if a given status is valid.
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses(), true);
    }
}
