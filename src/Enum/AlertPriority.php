<?php

namespace App\Enum;

enum AlertPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === strtoupper($name)) {
                return $case;
            }
        }
        return null;
    }

    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === strtolower($value)) {
                return $case;
            }
        }
        return null;
    }
}
