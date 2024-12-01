<?php

namespace App\Enum;

enum MovementChoice: string
{
    // le label de in entre guillemets
    case IN = 'IN';
    case OUT = 'OUT';

    public static function getAllTypes(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return match($this) {
            self::IN => 'Entrée',
            self::OUT => 'Sortie',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Entrée' => self::IN->value,
            'Sortie' => self::OUT->value,
        ];
    }
}