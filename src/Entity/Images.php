<?php

namespace App\Entity;

use App\Repository\ImagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImagesRepository::class)]
class Images
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'images', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipment $equipment = null; // Changement ici de $Equipment à $equipment

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment; // Changement ici de $Equipment à $equipment
    }

    public function setEquipment(?Equipment $equipment): static
    {
        $this->equipment = $equipment; // Changement ici de $Equipment à $equipment
        return $this;
    }
}
