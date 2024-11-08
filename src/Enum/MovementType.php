<?php

namespace App\Enum;

enum MovementType: string
{
    case IN = 'in';
    case OUT = 'out';

    public static function getAllTypes(): array
    {
        return [
            self::IN,
            self::OUT,
        ];
    }
}