<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Equipment;
use App\Enum\MovementChoice;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MovementRepository;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
class Movement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Equipment::class, inversedBy: 'movements', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Equipment $equipment;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $movementDate;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    private ?Category $category;

    public function __construct()
    {
        $this->movementDate = new \DateTimeImmutable(); // Initialisation par défaut de la date
    }

    // Getters and Setters

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
       
        $this->type = $type;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }
}
