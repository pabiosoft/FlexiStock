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

    /**
     * Vérifie si le stock est suffisant pour un article commandé
     */
    public function checkStock(OrderItem $orderItem): bool
    {
        $equipment = $orderItem->getEquipment();
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();

        return $orderItem->getQuantity() <= $availableStock;
    }

    /**
     * Réserve le stock pour un article commandé
     */
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
            // Créer une réservation
            $reservation = new Reservation();
            $reservation->setEquipment($equipment);
            $reservation->setReservedQuantity($quantity);
            $reservation->setStatus('reserved');
            $reservation->setReservationDate(new \DateTime());

            // Mettre à jour la quantité réservée
            $equipment->setReservedQuantity($equipment->getReservedQuantity() + $quantity);
            // mettre à jour le stock total
            // $equipment->setStockQuantity($equipment->getStockQuantity() - $quantity);

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

    /**
     * Finalise le stock après validation de la commande
     */
    public function finalizeStock(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();
        $quantity = $orderItem->getQuantity();

        try {
            // Déduire du stock réservé
            $equipment->setReservedQuantity($equipment->getReservedQuantity() - $quantity);
            // Déduire du stock total
            $equipment->setStockQuantity($equipment->getStockQuantity() - $quantity);

            // Mettre à jour le statut de la réservation
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

    /**
     * Annule la réservation de stock
     */
    // public function cancelReservation(OrderItem $orderItem): void
    // {
    //     $equipment = $orderItem->getEquipment();
    //     $quantity = $orderItem->getQuantity();

    //     try {
    //         // Libérer le stock réservé
    //         $equipment->setReservedQuantity($equipment->getReservedQuantity() - $quantity);

    //         // Mettre à jour le statut de la réservation
    //         $reservation = $this->entityManager->getRepository(Reservation::class)
    //             ->findOneBy([
    //                 'equipment' => $equipment,
    //                 'status' => 'reserved'
    //             ]);
    //         // Mettre à jour la quantité de la reservation
    //         $reservation->setReservedQuantity($reservation->getReservedQuantity() - $quantity);
    //         // mettre à jour le stock total
    //         $equipment->setStockQuantity($equipment->getStockQuantity() + $quantity);

    //         if ($reservation) {
    //             $reservation->setStatus('cancelled');
    //             $reservation->setReturnDate(new \DateTime());

    //         }

    //         $this->entityManager->flush();

    //         $this->logger->info(sprintf(
    //             'Réservation annulée pour %s: %d unités',
    //             $equipment->getName(),
    //             $quantity
    //         ));
    //     } catch (\Exception $e) {
    //         $this->logger->error(sprintf(
    //             'Erreur lors de l\'annulation de la réservation: %s',
    //             $e->getMessage()
    //         ));
    //         throw $e;
    //     }
    // }

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
            $equipment->setStockQuantity($equipment->getStockQuantity() + $quantity);

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


    /**
     * Vérifie si le stock est faible et nécessite un réapprovisionnement
     */
    public function checkLowStock(Equipment $equipment): bool
    {
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();
        return $availableStock <= $equipment->getMinThreshold();
    }

    /**
     * Ajuste le stock après un mouvement (entrée/sortie)
     */
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

            // Vérifier si le stock est faible après l'ajustement
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
