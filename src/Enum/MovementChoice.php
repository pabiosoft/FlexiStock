<?php

namespace App\Enum;

enum MovementChoice: string
{
    case IN = 'IN';
    case OUT = 'OUT';

    public static function getAllTypes(): array
    {
        return [
            self::IN,
            self::OUT,
        ];
    }
     public function toString(): string
    {
        return $this->value;
    }
}