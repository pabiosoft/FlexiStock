<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\enum\OrderStatus;
use App\enum\PaymentStatus;
use App\Repository\OrderRequestRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: OrderRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $orderDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'string', length: 20)]
    private string $paymentStatus ='pending';

    #[ORM\Column(type: 'string', length: 20)]
    private string $priority = 'normal';

    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orderRequest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'float')]
    private float $totalPrice = 0.0;

    #[ORM\Column(type: 'float')]
    private float $vatRate = 20.0;

    #[ORM\Column(type: 'float')]
    private float $vatAmount = 0.0;

    #[ORM\Column(type: 'float')]
    private float $subtotal = 0.0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $receivedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validatedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $notes = null;
    
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->orderDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setTimestamps(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orderDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderDate(): \DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setOrderRequest($this);
            $this->recalculateTotalPrice();
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrderRequest() === $this) {
                $item->setOrderRequest(null);
            }
            $this->recalculateTotalPrice();
        }

        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function recalculateTotalPrice(): self
    {
        // Calculate subtotal
        $this->subtotal = array_reduce($this->items->toArray(), function ($total, OrderItem $item) {
            return $total + $item->getTotalPrice();
        }, 0.0);

        // Calculate VAT amount
        $this->vatAmount = $this->subtotal * ($this->vatRate / 100);

        // Calculate total price including VAT
        $this->totalPrice = $this->subtotal + $this->vatAmount;

        return $this;
    }

    public function getVatRate(): float
    {
        return $this->vatRate;
    }

    public function setVatRate(float $vatRate): self
    {
        $this->vatRate = $vatRate;
        $this->recalculateTotalPrice();
        return $this;
    }

    public function getVatAmount(): float
    {
        return $this->vatAmount;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getReceivedAt(): ?\DateTimeInterface
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(?\DateTimeInterface $receivedAt): self
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function updateStatus(string $newStatus): self
    {
        $this->status = $newStatus;
        return $this;
    }

    public function updatePaymentStatus(string $newPaymentStatus): self
    {
        $this->paymentStatus = $newPaymentStatus;
        return $this;
    }
}
