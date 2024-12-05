<?php

namespace App\Event;

use App\Entity\Equipment;
use Symfony\Contracts\EventDispatcher\Event;

class EquipmentEvent extends Event
{
    public function __construct(
        private readonly Equipment $equipment
    ) {}

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }
}
