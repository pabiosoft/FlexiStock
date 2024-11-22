<?php

namespace App\Service;

use App\Entity\OrderRequest;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class OrderValidationService
{
    private EntityManagerInterface $entityManager;
    private StockService $stockService;

    public function __construct(
        EntityManagerInterface $entityManager,
        StockService $stockService
    ) {
        $this->entityManager = $entityManager;
        $this->stockService = $stockService;
    }

    public function validateOrder(OrderRequest $order): bool
    {
        // Vérifier le stock disponible
        foreach ($order->getItems() as $item) {
            if (!$this->stockService->checkStock($item)) {
                return false;
            }
        }

        // Vérifier les informations client
        if (!$this->validateCustomerInformation($order)) {
            return false;
        }

        // Vérifier le paiement
        if ($order->getPaymentStatus() !== PaymentStatus::SUCCESSFUL->value) {
            return false;
        }

        // Si tout est valide, mettre à jour le statut
        $order->setStatus(OrderStatus::VALIDATED->value);
        $this->entityManager->flush();

        return true;
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
            return false;
        }

        $order->setStatus($newStatus);
        
        if ($newStatus === OrderStatus::CANCELLED->value) {
            // Libérer le stock réservé
            foreach ($order->getItems() as $item) {
                $this->stockService->cancelReservation($item);
            }
        }

        $this->entityManager->flush();
        return true;
    }
}