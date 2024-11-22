<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case PROCESSED = 'processed';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING->value,
            self::VALIDATED->value,
            self::PROCESSED->value,
            self::SHIPPED->value,
            self::COMPLETED->value,
            self::CANCELLED->value,
            self::REFUNDED->value
        ];
    }

    public static function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['validated', 'cancelled'],
            'validated' => ['processed', 'cancelled'],
            'processed' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => ['refunded'],
            'cancelled' => ['refunded'],
            'refunded' => []
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
}