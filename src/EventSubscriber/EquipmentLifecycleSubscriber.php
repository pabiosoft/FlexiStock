<?php

namespace App\EventSubscriber;

use App\Entity\Equipment;
use App\Event\EquipmentEvent;
use App\Service\EquipmentLifecycleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EquipmentLifecycleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EquipmentLifecycleService $lifecycleService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'equipment.created' => 'onEquipmentChange',
            'equipment.updated' => 'onEquipmentChange',
            'equipment.warranty_updated' => 'onEquipmentChange',
            'equipment.maintenance_scheduled' => 'onMaintenanceScheduled',
        ];
    }

    public function onEquipmentChange(EquipmentEvent $event): void
    {
        $this->lifecycleService->checkEquipmentLifecycle();
    }

    public function onMaintenanceScheduled(EquipmentEvent $event): void
    {
        $this->lifecycleService->checkMaintenanceSchedule();
    }
}
