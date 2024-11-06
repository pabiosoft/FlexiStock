<?php

namespace App\Enum;

class AlertLevel
{
    const INFO = 'info';
    const WARNING = 'warning';
    const CRITICAL = 'critical';

    public static function getAllLevels(): array
    {
        return [
            self::INFO,
            self::WARNING,
            self::CRITICAL
        ];
    }
}