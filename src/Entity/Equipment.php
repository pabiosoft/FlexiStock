<?php

// src/Entity/Equipment.php

namespace App\Entity;

use App\Enum\EquipmentStatus;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\EquipmentRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment
{
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $brand;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $model;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $serialNumber;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $purchaseDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $warrantyDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $quantity;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $minThreshold;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    // Getters and Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getWarrantyDate(): ?\DateTimeInterface
    {
        return $this->warrantyDate;
    }

    public function setWarrantyDate(?\DateTimeInterface $warrantyDate): self
    {
        $this->warrantyDate = $warrantyDate;

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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getMinThreshold(): int
    {
        return $this->minThreshold;
    }

    public function setMinThreshold(int $minThreshold): self
    {
        $this->minThreshold = $minThreshold;

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
}