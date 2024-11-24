<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\OrderItem;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StockService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function checkStock(OrderItem $orderItem): bool
    {
        $equipment = $orderItem->getEquipment();
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();

        return $orderItem->getQuantity() <= $availableStock;
    }

    public function reserveStock(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();
        $quantity = $orderItem->getQuantity();

        if (!$this->checkStock($orderItem)) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour %s. Demandé: %d, Disponible: %d',
                $equipment->getName(),
                $quantity,
                $equipment->getStockQuantity() - $equipment->getReservedQuantity()
            ));
        }

        try {
            // Create reservation
            $reservation = new Reservation();
            $reservation->setEquipment($equipment);
            $reservation->setReservedQuantity($quantity);
            $reservation->setStatus('reserved');
            $reservation->setReservationDate(new \DateTime());

            // Update reserved quantity
            $equipment->setReservedQuantity($equipment->getReservedQuantity() + $quantity);

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Stock réservé pour %s: %d unités',
                $equipment->getName(),
                $quantity
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Erreur lors de la réservation du stock: %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    public function verifyReservation(OrderItem $orderItem): bool
    {
        try {
            $equipment = $orderItem->getEquipment();
            $reservation = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy([
                    'equipment' => $equipment,
                    'status' => 'reserved'
                ]);

            if (!$reservation) {
                $this->logger->warning(sprintf(
                    'No reservation found for equipment %s in order %d',
                    $equipment->getName(),
                    $orderItem->getOrderRequest()->getId()
                ));
                return false;
            }

            if ($reservation->getReservedQuantity() !== $orderItem->getQuantity()) {
                $this->logger->warning(sprintf(
                    'Reservation quantity mismatch for equipment %s: Reserved: %d, Ordered: %d',
                    $equipment->getName(),
                    $reservation->getReservedQuantity(),
                    $orderItem->getQuantity()
                ));
                return false;
            }

            $this->logger->info(sprintf(
                'Reservation verified for equipment %s: %d units',
                $equipment->getName(),
                $orderItem->getQuantity()
            ));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Error verifying reservation: %s',
                $e->getMessage()
            ));
            return false;
        }
    }

    public function finalizeStock(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();
        $quantity = $orderItem->getQuantity();

        try {
            // Deduct from reserved stock
            $equipment->setReservedQuantity($equipment->getReservedQuantity() - $quantity);
            // Deduct from total stock
            $equipment->setStockQuantity($equipment->getStockQuantity() - $quantity);

            // Update reservation status
            $reservation = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy([
                    'equipment' => $equipment,
                    'status' => 'reserved'
                ]);

            if ($reservation) {
                $reservation->setStatus('completed');
                $reservation->setReturnDate(new \DateTime());
            }

            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Stock finalisé pour %s: %d unités',
                $equipment->getName(),
                $quantity
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Erreur lors de la finalisation du stock: %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    public function cancelReservation(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();
        $quantity = $orderItem->getQuantity();

        try {
            // Find the relevant reservation
            $reservation = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy([
                    'equipment' => $equipment,
                    'status' => 'reserved'
                ]);

            if (!$reservation || $reservation->getReservedQuantity() < $quantity) {
                throw new \LogicException(sprintf(
                    'Impossible d\'annuler la réservation : quantité invalide pour %s. Réservé: %d, Demandé à annuler: %d',
                    $equipment->getName(),
                    $reservation ? $reservation->getReservedQuantity() : 0,
                    $quantity
                ));
            }

            // Adjust the reservation and equipment quantities
            $reservation->setReservedQuantity($reservation->getReservedQuantity() - $quantity);
            if ($reservation->getReservedQuantity() === 0) {
                $reservation->setStatus('cancelled');
                $reservation->setReturnDate(new \DateTime());
            }

            $equipment->setReservedQuantity($equipment->getReservedQuantity() - $quantity);

            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Réservation annulée pour %s: %d unités',
                $equipment->getName(),
                $quantity
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Erreur lors de l\'annulation de la réservation: %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }

    public function checkLowStock(Equipment $equipment): bool
    {
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();
        return $availableStock <= $equipment->getMinThreshold();
    }

    public function adjustStock(Equipment $equipment, int $quantity, string $type): void
    {
        $currentStock = $equipment->getStockQuantity();

        if ($type === 'OUT' && $quantity > ($currentStock - $equipment->getReservedQuantity())) {
            throw new \LogicException(sprintf(
                'Stock insuffisant pour %s. Demandé: %d, Disponible: %d',
                $equipment->getName(),
                $quantity,
                $currentStock - $equipment->getReservedQuantity()
            ));
        }

        try {
            $newStock = $type === 'IN' ? $currentStock + $quantity : $currentStock - $quantity;
            $equipment->setStockQuantity($newStock);

            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Stock ajusté pour %s: %d unités (%s)',
                $equipment->getName(),
                $quantity,
                $type
            ));

            if ($this->checkLowStock($equipment)) {
                $this->logger->warning(sprintf(
                    'Stock faible pour %s: %d unités restantes',
                    $equipment->getName(),
                    $newStock - $equipment->getReservedQuantity()
                ));
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Erreur lors de l\'ajustement du stock: %s',
                $e->getMessage()
            ));
            throw $e;
        }
    }
}