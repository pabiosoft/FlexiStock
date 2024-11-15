<?php

namespace App\Service;

use App\Entity\Movement;
use App\Entity\Equipment;
use App\Repository\MovementRepository;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;

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

    /**
     * Creates a new movement entry and updates the equipment quantity.
     */
    public function createMovement(Movement $movement): void
    {
        $this->validateMovementType($movement);

        $equipment = $this->equipmentRepository->find($movement->getEquipment()->getId());
        if (!$equipment) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        if ($movement->getType() === 'IN') {
            $equipment->setQuantity($equipment->getQuantity() + $movement->getQuantity());
        } elseif ($movement->getType() === 'OUT') {
            if ($equipment->getQuantity() >= $movement->getQuantity()) {
                $equipment->setQuantity($equipment->getQuantity() - $movement->getQuantity());
            } else {
                throw new \InvalidArgumentException('Insufficient equipment stock for this movement.');
            }
        }

        $this->entityManager->persist($movement);
        $this->entityManager->persist($equipment);
        $this->entityManager->flush();
    }

    /**
     * Validates the movement type.
     */
    private function validateMovementType(Movement $movement): void
    {
        $validTypes = ['IN', 'OUT'];
        if (!in_array($movement->getType(), $validTypes, true)) {
            throw new \InvalidArgumentException('Invalid movement type. Allowed types are: IN, OUT.');
        }
    }

    /**
     * Retrieves all movements from the database.
     */
    public function getAllMovements(): array
    {
        return $this->movementRepository->findAll();
    }

    /**
     * Retrieves a single movement by its ID.
     */
    public function getMovementById(int $id): ?Movement
    {
        return $this->movementRepository->find($id);
    }

    /**
     * Updates an existing movement entry.
     */
    public function updateMovement(Movement $movement): void
    {
        // Find the original movement record from the database
        $originalMovement = $this->movementRepository->find($movement->getId());

        if (!$originalMovement) {
            throw new \InvalidArgumentException('Original movement not found.');
        }

        $equipment = $this->equipmentRepository->find($movement->getEquipment()->getId());
        if (!$equipment) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        // Revert the equipment quantity based on the original movement type
        if ($originalMovement->getType() === 'IN') {
            $equipment->setQuantity($equipment->getQuantity() - $originalMovement->getQuantity());
        } elseif ($originalMovement->getType() === 'OUT') {
            $equipment->setQuantity($equipment->getQuantity() + $originalMovement->getQuantity());
        }

        // Apply the updated movement quantity based on the new movement type
        if ($movement->getType() === 'IN') {
            $equipment->setQuantity($equipment->getQuantity() + $movement->getQuantity());
        } elseif ($movement->getType() === 'OUT') {
            if ($equipment->getQuantity() >= $movement->getQuantity()) {
                $equipment->setQuantity($equipment->getQuantity() - $movement->getQuantity());
            } else {
                throw new \InvalidArgumentException('Insufficient equipment stock for this movement.');
            }
        }

        // Persist changes to the movement and equipment
        try {
            $this->entityManager->persist($movement);
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update movement: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a movement and adjusts the equipment quantity.
     */
    public function deleteMovement(Movement $movement): void
    {
        if (null === $equipment = $movement->getEquipment()) {
            throw new \InvalidArgumentException('Equipment not found.');
        }

        try {
            if ($movement->getType() === 'IN') {
                $equipment->setQuantity($equipment->getQuantity() - $movement->getQuantity());
            } elseif ($movement->getType() === 'OUT') {
                $equipment->setQuantity($equipment->getQuantity() + $movement->getQuantity());
            }

            $this->entityManager->remove($movement);
            $this->entityManager->persist($equipment);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred while deleting the movement.', 0, $e);
        }
    }

    public function deleteEquipmentWithMovements(Equipment $equipment): void
    {
        // Find all movements associated with the equipment
        $movements = $this->movementRepository->findBy(['equipment' => $equipment]);

        // Delete each movement
        foreach ($movements as $movement) {
            $this->entityManager->remove($movement);
        }

        // Now delete the equipment
        $this->entityManager->remove($equipment);
        $this->entityManager->flush();
    }
}
