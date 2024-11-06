<?php

namespace App\Enum;

class ReservationStatus
{
    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const CANCELED = 'canceled';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::COMPLETED,
            self::CANCELED
        ];
    }
}