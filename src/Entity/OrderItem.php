<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\OrderItemRepository;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderRequest::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrderRequest $orderRequest = null;

    #[ORM\ManyToOne(targetEntity: Equipment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipment $equipment = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 1;

    #[ORM\Column(type: 'float')]
    private float $unitPrice = 0.0;

    #[ORM\Column(type: 'float')]
    private float $totalPrice = 0.0;

    public function __construct()
    {
        $this->calculateTotal();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRequest(): ?OrderRequest
    {
        return $this->orderRequest;
    }

    public function setOrderRequest(?OrderRequest $orderRequest): self
    {
        $this->orderRequest = $orderRequest;
        return $this;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(?Equipment $equipment): self
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
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero.");
        }

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
        if ($unitPrice < 0) {
            throw new \InvalidArgumentException("Unit price cannot be negative.");
        }

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

    public function updateTotal(): self
    {
        $this->calculateTotal();
        return $this;
    }
}
