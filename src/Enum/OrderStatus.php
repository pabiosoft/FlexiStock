<?php

namespace App\Enum;

class OrderStatus
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const RECEIVED = 'received';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::RECEIVED
        ];
    }
}