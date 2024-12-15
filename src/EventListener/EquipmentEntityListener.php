<?php

namespace App\EventListener;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postLoad, entity: Equipment::class)]
class EquipmentEntityListener
{
    public function __construct(
        #[Autowire(service: 'event_dispatcher')]
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function postLoad(Equipment $equipment): void
    {
        $equipment->setEventDispatcher($this->eventDispatcher);
    }
}
