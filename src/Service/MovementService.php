<?php

namespace App\Service;

use App\Entity\Movement;
use App\Entity\Equipment;
use App\Repository\MovementRepository;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;

class MovementService
{
    private EntityManagerInterface $entityManager;
    private MovementRepository $movementRepository;
    private EquipmentRepository $equipmentRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MovementRepository $movementRepository,
        EquipmentRepository $equipmentRepository
    ) {
        $this->entityManager = $entityManager;
        $this->movementRepository = $movementRepository;
        $this->equipmentRepository = $equipmentRepository;
    }

    public function createMovement(Movement $movement): void
    {
        $this->validateMovementType($movement);

        $equipment = $this->equipmentRepository->find($movement->getEquipment()->getId());
        if (!$equipment) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        if ($movement->getType() === 'IN') {
            $equipment->setStockQuantity($equipment->getStockQuantity() + $movement->getQuantity());
        } elseif ($movement->getType() === 'OUT') {
            if ($equipment->getStockQuantity() >= $movement->getQuantity()) {
                $equipment->setStockQuantity($equipment->getStockQuantity() - $movement->getQuantity());
            } else {
                throw new \InvalidArgumentException('Insufficient equipment stock for this movement.');
            }
        }

        $this->entityManager->persist($movement);
        $this->entityManager->persist($equipment);
        $this->entityManager->flush();
    }

    private function validateMovementType(Movement $movement): void
    {
        $validTypes = ['IN', 'OUT'];
        if (!in_array($movement->getType(), $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid movement type. Allowed types are: IN, OUT.');
        }
    }

    public function getMovementsByEquipment(Equipment $equipment, \DateTime $startDate, \DateTime $endDate): ArrayCollection
    {
        return $this->movementRepository->findByEquipmentAndDateRange($equipment, $startDate, $endDate);
    }

    public function getAllMovements(): array
    {
        return $this->movementRepository->findAll();
    }

    public function getMovementById(int $id): ?Movement
    {
        return $this->movementRepository->find($id);
    }

    public function updateMovement(Movement $movement): void
    {
        $originalMovement = $this->movementRepository->find($movement->getId());

        if (!$originalMovement) {
            throw new \InvalidArgumentException('Original movement not found.');
        }

        $equipment = $this->equipmentRepository->find($movement->getEquipment()->getId());
        if (!$equipment) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        if ($originalMovement->getType() === 'IN') {
            $equipment->setStockQuantity($equipment->getStockQuantity() - $originalMovement->getQuantity());
        } elseif ($originalMovement->getType() === 'OUT') {
            $equipment->setStockQuantity($equipment->getStockQuantity() + $originalMovement->getQuantity());
        }

        if ($movement->getType() === 'IN') {
            $equipment->setStockQuantity($equipment->getStockQuantity() + $movement->getQuantity());
        } elseif ($movement->getType() === 'OUT') {
            if ($equipment->getStockQuantity() >= $movement->getQuantity()) {
                $equipment->setStockQuantity($equipment->getStockQuantity() - $movement->getQuantity());
            } else {
                throw new \InvalidArgumentException('Insufficient equipment stock for this movement.');
            }
        }

        try {
            $this->entityManager->persist($movement);
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update movement: ' . $e->getMessage());
        }
    }

    public function deleteMovement(Movement $movement): void
    {
        if (null === $equipment = $movement->getEquipment()) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        try {
            if ($movement->getType() === 'IN') {
                $equipment->setStockQuantity($equipment->getStockQuantity() - $movement->getQuantity());
            } elseif ($movement->getType() === 'OUT') {
                $equipment->setStockQuantity($equipment->getStockQuantity() + $movement->getQuantity());
                $equipment->setStockQuantity(max(0, $equipment->getStockQuantity()));
            }

            $this->entityManager->remove($movement);
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred while deleting the movement.', 0, $e);
        }
    }
}