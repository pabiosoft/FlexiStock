<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueEntity(fields={"slug"}, message="Un article existe déjà avec ce slug")
 */
trait SlugTrait
{
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function initializeSlug(string $text): void
    {
        if (empty($text)) {
            throw new \InvalidArgumentException('Slug initialization requires a non-empty string.');
        }
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($text)));
        $baseSlug = trim($baseSlug, '-'); // Remove trailing hyphens
        $this->slug = $baseSlug . '-' . uniqid();
    }
}
