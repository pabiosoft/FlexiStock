<?php

namespace App\Enum;

enum ReportType: string
{
    case INVENTORY = 'inventory';
    case CONSUMPTION = 'consumption';
    case STATISTICS = 'statistics';
}
