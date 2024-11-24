<?php

namespace App\Entity;

use App\Enum\MovementChoice;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MovementRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Movement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Equipment::class, inversedBy: 'movements', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Equipment must be specified')]
    private Equipment $equipment;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(callback: [MovementChoice::class, 'getAllTypes'], message: 'Invalid movement type')]
    private string $type;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Quantity is required')]
    #[Assert\Positive(message: 'Quantity must be positive')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $movementDate;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    private ?Category $category;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reference;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = [];

    public function __construct()
    {
        $this->movementDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->type === MovementChoice::OUT->value && $this->quantity > $this->equipment->getAvailableStock()) {
            throw new \LogicException('Insufficient stock available for this movement');
        }
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!in_array($type, array_column(MovementChoice::cases(), 'value'))) {
            throw new \InvalidArgumentException('Invalid movement type');
        }
        $this->type = $type;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        $this->quantity = $quantity;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getMovementDate(): \DateTimeInterface
    {
        return $this->movementDate;
    }

    public function setMovementDate(\DateTimeInterface $movementDate): self
    {
        $this->movementDate = $movementDate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function addDetail(string $key, mixed $value): self
    {
        if ($this->details === null) {
            $this->details = [];
        }
        $this->details[$key] = $value;
        return $this;
    }

    public function removeDetail(string $key): self
    {
        if (isset($this->details[$key])) {
            unset($this->details[$key]);
        }
        return $this;
    }
}