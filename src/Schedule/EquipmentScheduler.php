<?php

namespace App\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class EquipmentScheduler implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->addCommand('app:check-equipment-lifecycle')
            ->description('Check equipment lifecycle and send notifications')
            ->dailyAt('00:00')  // Run daily at midnight
            ->emailOnFailure('your-email@example.com');  // Replace with your email
    }
}
