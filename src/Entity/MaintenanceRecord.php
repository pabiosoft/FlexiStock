<?php

namespace App\Entity;

use App\Repository\MaintenanceRecordRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaintenanceRecordRepository::class)]
class MaintenanceRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Equipment::class, inversedBy: 'maintenanceRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private Equipment $equipment;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $maintenanceDate;

    #[ORM\Column(type: 'string', length: 50)]
    private string $maintenanceType;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $cost;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $performedBy;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $nextMaintenanceDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    public function __construct()
    {
        $this->maintenanceDate = new \DateTimeImmutable();
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

    public function getMaintenanceDate(): \DateTimeInterface
    {
        return $this->maintenanceDate;
    }

    public function setMaintenanceDate(\DateTimeInterface $maintenanceDate): self
    {
        $this->maintenanceDate = $maintenanceDate;
        return $this;
    }

    public function getMaintenanceType(): string
    {
        return $this->maintenanceType;
    }

    public function setMaintenanceType(string $maintenanceType): self
    {
        $this->maintenanceType = $maintenanceType;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function getPerformedBy(): User
    {
        return $this->performedBy;
    }

    public function setPerformedBy(User $performedBy): self
    {
        $this->performedBy = $performedBy;
        return $this;
    }

    public function getNextMaintenanceDate(): \DateTimeInterface
    {
        return $this->nextMaintenanceDate;
    }

    public function setNextMaintenanceDate(\DateTimeInterface $nextMaintenanceDate): self
    {
        $this->nextMaintenanceDate = $nextMaintenanceDate;
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
}
