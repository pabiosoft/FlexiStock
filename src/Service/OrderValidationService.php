<?php

namespace App\Service;

use App\Entity\OrderRequest;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderValidationService
{
    private EntityManagerInterface $entityManager;
    private StockService $stockService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        StockService $stockService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stockService = $stockService;
        $this->logger = $logger;
    }

    public function validateOrder(OrderRequest $order): array
    {
        $errors = [];

        // Stock validation
        foreach ($order->getItems() as $item) {
            if (!$this->stockService->checkStock($item)) {
                $errors[] = sprintf('Stock insuffisant pour l\'article %s.', $item->getEquipment()->getName());
            }
        }

        // Customer validation
        if (!$this->validateCustomerInformation($order)) {
            $errors[] = 'Informations client invalides.';
        }

        // Payment validation
        if ($order->getPaymentStatus() !== PaymentStatus::SUCCESSFUL->value) {
            $errors[] = 'Le paiement n\'a pas été validé.';
        }

        // If no errors, update status
        if (empty($errors)) {
            $order->setStatus(OrderStatus::VALIDATED->value);
            $this->entityManager->flush();
        }

        return $errors;
    }

    private function validateCustomerInformation(OrderRequest $order): bool
    {
        $customer = $order->getCustomer();
        return $customer !== null && $customer->getEmail() !== null;
    }

    public function canUpdateStatus(OrderRequest $order, string $newStatus): bool
    {
        return OrderStatus::canTransitionTo($order->getStatus(), $newStatus);
    }

    public function updateOrderStatus(OrderRequest $order, string $newStatus): bool
    {
        if (!$this->canUpdateStatus($order, $newStatus)) {
            $this->logger->warning(sprintf(
                'Transition non autorisée: %s vers %s pour la commande %d.',
                $order->getStatus(),
                $newStatus,
                $order->getId()
            ));
            return false;
        }

        try {
            // Handle status-specific updates
            switch ($newStatus) {
                case OrderStatus::CANCELLED->value:
                    $this->handleCancelledStatus($order);
                    break;

                case OrderStatus::COMPLETED->value:
                    $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
                    break;

                case OrderStatus::REFUNDED->value:
                    $order->setPaymentStatus(PaymentStatus::REFUNDED->value);
                    break;

                case OrderStatus::PENDING->value:
                    $order->setPaymentStatus(PaymentStatus::PROCESSING->value);
                    break;
                

                case OrderStatus::SHIPPED->value:
                    $this->handleShippedStatus($order);
                    break;

                default:
                    $this->logger->info(sprintf(
                        'Aucune action supplémentaire requise pour la transition vers %s pour la commande %d.',
                        $newStatus,
                        $order->getId()
                    ));
            }

            $order->setStatus($newStatus);
            $this->entityManager->flush();

            $this->logger->info(sprintf(
                'Statut de la commande %d mis à jour vers %s.',
                $order->getId(),
                $newStatus
            ));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Erreur lors de la mise à jour du statut de la commande %d vers %s: %s',
                $order->getId(),
                $newStatus,
                $e->getMessage()
            ));
            throw $e;
        }
    }

    private function handleCancelledStatus(OrderRequest $order): void
    {
        $order->setPaymentStatus(PaymentStatus::REFUNDED->value);

        foreach ($order->getItems() as $item) {
            $this->stockService->cancelReservation($item);
        }

        $this->logger->info(sprintf(
            'Commande %d annulée: le stock a été libéré et le paiement remboursé.',
            $order->getId()
        ));
    }

    private function handleShippedStatus(OrderRequest $order): void
    {
        foreach ($order->getItems() as $item) {
            $this->stockService->finalizeStock($item);
            // Update reservation logic here if necessary
        }

        $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
        $this->logger->info(sprintf(
            'Commande %d expédiée: le stock a été finalisé.',
            $order->getId()
        ));

        // Optionally, handle delivery date updates or reservation updates
    }
}
