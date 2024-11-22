<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\OrderDetailRepository;

#[ORM\Entity(repositoryClass: OrderDetailRepository::class)]
class OrderDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderRequest::class, inversedBy: 'details')]
    #[ORM\JoinColumn(nullable: false)]
    private OrderRequest $orderRequest;

    #[ORM\ManyToOne(targetEntity: Equipment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Equipment $equipment;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'float')]
    private float $unitPrice;

    #[ORM\Column(type: 'float')]
    private float $totalPrice;

    public function __construct()
    {
        $this->calculateTotal();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRequest(): OrderRequest
    {
        return $this->orderRequest;
    }

    public function setOrderRequest(OrderRequest $orderRequest): self
    {
        $this->orderRequest = $orderRequest;
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
        $this->calculateTotal();
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        $this->calculateTotal();
        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    private function calculateTotal(): void
    {
        $this->totalPrice = $this->quantity * $this->unitPrice;
    }
}
