<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Images;
use App\Entity\Category;
use App\Entity\Trait\SlugTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\EquipmentRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Equipment
{
    use SlugTrait;

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
    #[Assert\GreaterThanOrEqual(0)]
    private int $stockQuantity;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\GreaterThanOrEqual(0)]
    private int $reservedQuantity = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\GreaterThanOrEqual(0)]
    private int $minThreshold;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $price;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $salePrice;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastMaintenanceDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $nextMaintenanceDate;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'equipmentItems')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $assignedUser;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: Images::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $images;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: "equipment_subcategories")]
    private Collection $subcategories;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: Movement::class, cascade: ['remove'])]
    private Collection $movements;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: MaintenanceRecord::class, cascade: ['remove'])]
    private Collection $maintenanceRecords;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: Movement::class)]
    private Collection $stockMovements;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $lowStockThreshold = 0;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->subcategories = new ArrayCollection();
        $this->movements = new ArrayCollection();
        $this->maintenanceRecords = new ArrayCollection();
        $this->stockMovements = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->stockQuantity = 0;
        $this->reservedQuantity = 0;
        $this->minThreshold = 1;
    }

    // Méthodes de gestion du stock
    public function getAvailableStock(): int
    {
        return $this->stockQuantity - $this->reservedQuantity;
    }

    public function canReserve(int $quantity): bool
    {
        return $quantity <= $this->getAvailableStock();
    }

    public function reserve(int $quantity): self
    {
        if (!$this->canReserve($quantity)) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour %s. Demandé: %d, Disponible: %d',
                $this->name,
                $quantity,
                $this->getAvailableStock()
            ));
        }

        $this->reservedQuantity += $quantity;
        return $this;
    }

    public function release(int $quantity): self
    {
        if ($quantity > $this->reservedQuantity) {
            throw new \LogicException(sprintf(
                'Impossible de libérer plus que la quantité réservée. Demandé: %d, Réservé: %d',
                $quantity,
                $this->reservedQuantity
            ));
        }

        $this->reservedQuantity -= $quantity;
        return $this;
    }

    public function adjustStock(int $quantity, string $type): self
    {
        if ($type === 'OUT' && $quantity > $this->getAvailableStock()) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour %s. Demandé: %d, Disponible: %d',
                $this->name,
                $quantity,
                $this->getAvailableStock()
            ));
        }

        $this->stockQuantity = $type === 'IN' 
            ? $this->stockQuantity + $quantity 
            : $this->stockQuantity - $quantity;

        return $this;
    }

    public function isLowStock(): bool
    {
        return $this->getAvailableStock() <= $this->minThreshold;
    }

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

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        if ($stockQuantity < 0) {
            throw new \InvalidArgumentException('La quantité en stock ne peut pas être négative.');
        }
        $this->stockQuantity = $stockQuantity;
        return $this;
    }

    public function getReservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function setReservedQuantity(int $reservedQuantity): self
    {
        if ($reservedQuantity < 0) {
            throw new \InvalidArgumentException('La quantité réservée ne peut pas être négative.');
        }
        if ($reservedQuantity > $this->stockQuantity) {
            throw new \InvalidArgumentException('La quantité réservée ne peut pas dépasser le stock total.');
        }
        $this->reservedQuantity = $reservedQuantity;
        return $this;
    }

    public function getMinThreshold(): int
    {
        return $this->minThreshold;
    }

    public function setMinThreshold(int $minThreshold): self
    {
        if ($minThreshold < 0) {
            throw new \InvalidArgumentException('Le seuil minimum ne peut pas être négatif.');
        }
        $this->minThreshold = $minThreshold;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        if ($price !== null && $price < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif.');
        }
        $this->price = $price;
        return $this;
    }

    public function getSalePrice(): ?float
    {
        return $this->salePrice;
    }

    public function setSalePrice(?float $salePrice): self
    {
        if ($salePrice !== null && $salePrice < 0) {
            throw new \InvalidArgumentException('Le prix de vente ne peut pas être négatif.');
        }
        $this->salePrice = $salePrice;
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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getAssignedUser(): ?User
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?User $assignedUser): self
    {
        $this->assignedUser = $assignedUser;
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Images $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setEquipment($this);
        }
        return $this;
    }

    public function removeImage(Images $image): self
    {
        if ($this->images->removeElement($image)) {
            if ($image->getEquipment() === $this) {
                $image->setEquipment(null);
            }
        }
        return $this;
    }

    public function getSubcategories(): Collection
    {
        return $this->subcategories;
    }

    public function addSubcategory(Category $subcategory): self
    {
        if (!$this->subcategories->contains($subcategory)) {
            $this->subcategories[] = $subcategory;
        }
        return $this;
    }

    public function removeSubcategory(Category $subcategory): self
    {
        $this->subcategories->removeElement($subcategory);
        return $this;
    }

    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function addMovement(Movement $movement): self
    {
        if (!$this->movements->contains($movement)) {
            $this->movements[] = $movement;
            $movement->setEquipment($this);
        }
        return $this;
    }

    public function removeMovement(Movement $movement): self
    {
        if ($this->movements->removeElement($movement)) {
            if ($movement->getEquipment() === $this) {
                $movement->setEquipment(null);
            }
        }
        return $this;
    }

    public function getMaintenanceRecords(): Collection
    {
        return $this->maintenanceRecords;
    }

    public function addMaintenanceRecord(MaintenanceRecord $maintenanceRecord): self
    {
        if (!$this->maintenanceRecords->contains($maintenanceRecord)) {
            $this->maintenanceRecords[] = $maintenanceRecord;
            $maintenanceRecord->setEquipment($this);
            $this->lastMaintenanceDate = $maintenanceRecord->getMaintenanceDate();
            $this->nextMaintenanceDate = $maintenanceRecord->getNextMaintenanceDate();
        }

        return $this;
    }

    public function removeMaintenanceRecord(MaintenanceRecord $maintenanceRecord): self
    {
        if ($this->maintenanceRecords->removeElement($maintenanceRecord)) {
            if ($maintenanceRecord->getEquipment() === $this) {
                $maintenanceRecord->setEquipment(null);
            }
        }

        return $this;
    }

    public function getLastMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->lastMaintenanceDate;
    }

    public function setLastMaintenanceDate(?\DateTimeInterface $lastMaintenanceDate): self
    {
        $this->lastMaintenanceDate = $lastMaintenanceDate;
        return $this;
    }

    public function getNextMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->nextMaintenanceDate;
    }

    public function setNextMaintenanceDate(?\DateTimeInterface $nextMaintenanceDate): self
    {
        $this->nextMaintenanceDate = $nextMaintenanceDate;
        return $this;
    }

    public function isMaintenanceNeeded(): bool
    {
        if ($this->nextMaintenanceDate === null) {
            return false;
        }
        return $this->nextMaintenanceDate <= new \DateTime();
    }

    public function getLowStockThreshold(): ?int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(?int $lowStockThreshold): self
    {
        $this->lowStockThreshold = $lowStockThreshold;
        return $this;
    }

    /**
     * @return Collection<int, Movement>
     */
    public function getStockMovements(): Collection
    {
        return $this->stockMovements;
    }

    public function addStockMovement(Movement $movement): self
    {
        if (!$this->stockMovements->contains($movement)) {
            $this->stockMovements[] = $movement;
            $movement->setEquipment($this);
        }
        return $this;
    }

    public function removeStockMovement(Movement $movement): self
    {
        if ($this->stockMovements->removeElement($movement)) {
            // set the owning side to null (unless already changed)
            if ($movement->getEquipment() === $this) {
                $movement->setEquipment(null);
            }
        }
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        $this->initializeSlug($this->getName());
    }

    public function __toString(): string
    {
        return $this->name;
    }
}