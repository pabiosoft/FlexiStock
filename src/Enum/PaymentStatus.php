<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING->value,
            self::CONFIRMED->value,
            self::PROCESSING->value,
            self::SUCCESSFUL->value,
            self::FAILED->value,
            self::REFUNDED->value
        ];
    }

    public static function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['processing', 'failed'],
            'confirmed' => ['processing', 'failed'],
            'processing' => ['successful', 'failed'],
            'successful' => ['refunded'],
            'failed' => ['processing'],
            'refunded' => []
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
}