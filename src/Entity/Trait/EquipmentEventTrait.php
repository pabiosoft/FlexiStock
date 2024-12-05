<?php

namespace App\Entity\Trait;

use App\Event\EquipmentEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

trait EquipmentEventTrait
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    #[Autowire]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    private function dispatchEvent(string $eventName): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new EquipmentEvent($this), $eventName);
        }
    }
}
