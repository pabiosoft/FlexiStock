<?php

namespace App\Enum;

class RequestStatus
{
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED
        ];
    }
}