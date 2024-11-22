<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\RequestItemRepository;

#[ORM\Entity(repositoryClass: RequestItemRepository::class)]
class RequestItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Request::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Request $request;

    #[ORM\ManyToOne(targetEntity: Equipment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Equipment $equipment;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(Equipment $equipment): self
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }
}
