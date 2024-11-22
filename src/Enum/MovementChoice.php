<?php

namespace App\Enum;

enum MovementChoice: string
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
     public function toString(): string
    {
        return $this->value;
    }
}