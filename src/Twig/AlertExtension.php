<?php

namespace App\Twig;

use App\Enum\AlertCategory;
use App\Enum\AlertLevel;
use App\Enum\AlertPriority;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AlertExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('alert_categories', [$this, 'getAlertCategories']),
            new TwigFunction('alert_levels', [$this, 'getAlertLevels']),
            new TwigFunction('alert_priorities', [$this, 'getAlertPriorities']),
        ];
    }

    public function getAlertCategories(): array
    {
        return [
            'MAINTENANCE' => AlertCategory::MAINTENANCE->value,
            'STOCK' => AlertCategory::STOCK->value,
            'CALIBRATION' => AlertCategory::CALIBRATION->value,
            'WARRANTY' => AlertCategory::WARRANTY->value,
        ];
    }

    public function getAlertLevels(): array
    {
        return [
            'INFO' => AlertLevel::INFO->value,
            'WARNING' => AlertLevel::WARNING->value,
            'ERROR' => AlertLevel::ERROR->value,
            'SUCCESS' => AlertLevel::SUCCESS->value,
            'CRITICAL' => AlertLevel::CRITICAL->value,
        ];
    }

    public function getAlertPriorities(): array
    {
        return [
            'LOW' => AlertPriority::LOW->value,
            'MEDIUM' => AlertPriority::MEDIUM->value,
            'HIGH' => AlertPriority::HIGH->value,
        ];
    }
}
