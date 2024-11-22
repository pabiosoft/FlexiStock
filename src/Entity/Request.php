<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\RequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: RequestRepository::class)]
class Request
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $requester;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $requestedAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending'; // pending, approved, rejected, planned

    #[ORM\OneToMany(targetEntity: RequestItem::class, mappedBy: 'request', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->requestedAt = new \DateTime();
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRequester(): User
    {
        return $this->requester;
    }

    public function setRequester(User $requester): self
    {
        $this->requester = $requester;
        return $this;
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
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

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(RequestItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setRequest($this);
        }

        return $this;
    }

    public function removeItem(RequestItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getRequest() === $this) {
                $item->setRequest(null);
            }
        }

        return $this;
    }
}
