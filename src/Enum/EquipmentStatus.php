<?php

namespace App\Enum;

class EquipmentStatus
{
    const ACTIVE = 'active';
    const EXPIRED = 'expired';
    const OBSOLETE = 'obsolete';

    public static function getAllStatuses(): array
    {
        return [
            self::ACTIVE,
            self::EXPIRED,
            self::OBSOLETE
        ];
    }
}