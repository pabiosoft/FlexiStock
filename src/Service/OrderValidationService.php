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

    public function validateOrder(OrderRequest $order): bool
    {
        try {
            // Check payment status
            if ($order->getPaymentStatus() !== PaymentStatus::SUCCESSFUL->value) {
                $this->logger->warning('Order validation failed: Payment not successful', [
                    'order_id' => $order->getId(),
                    'payment_status' => $order->getPaymentStatus()
                ]);
                return false;
            }

            // Check stock availability
            foreach ($order->getItems() as $item) {
                if (!$this->stockService->checkStock($item)) {
                    $this->logger->warning('Order validation failed: Insufficient stock', [
                        'order_id' => $order->getId(),
                        'equipment' => $item->getEquipment()->getName(),
                        'requested' => $item->getQuantity(),
                        'available' => $item->getEquipment()->getAvailableStock()
                    ]);
                    return false;
                }
            }

            // Update order status
            $order->setStatus(OrderStatus::VALIDATED->value);
            $this->entityManager->flush();

            $this->logger->info('Order validated successfully', [
                'order_id' => $order->getId()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error validating order: ' . $e->getMessage(), [
                'order_id' => $order->getId(),
                'exception' => $e
            ]);
            return false;
        }
    }

    public function updateOrderStatus(OrderRequest $order, string $newStatus): bool
    {
        try {
            if (!OrderStatus::canTransitionTo($order->getStatus(), $newStatus)) {
                $this->logger->warning('Invalid status transition', [
                    'order_id' => $order->getId(),
                    'current_status' => $order->getStatus(),
                    'new_status' => $newStatus
                ]);
                return false;
            }

            // Handle status-specific actions
            switch ($newStatus) {
                case OrderStatus::PROCESSED->value:
                    $this->handleProcessedStatus($order);
                    break;

                case OrderStatus::SHIPPED->value:
                    $this->handleShippedStatus($order);
                    break;

                case OrderStatus::COMPLETED->value:
                    $this->handleCompletedStatus($order);
                    break;

                case OrderStatus::CANCELLED->value:
                    $this->handleCancelledStatus($order);
                    break;
            }

            $order->setStatus($newStatus);
            $this->entityManager->flush();

            $this->logger->info('Order status updated successfully', [
                'order_id' => $order->getId(),
                'new_status' => $newStatus
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating order status: ' . $e->getMessage(), [
                'order_id' => $order->getId(),
                'exception' => $e
            ]);
            return false;
        }
    }

    private function handleProcessedStatus(OrderRequest $order): void
    {
        // Verify stock reservations
        foreach ($order->getItems() as $item) {
            $this->stockService->verifyReservation($item);
        }
    }

    private function handleShippedStatus(OrderRequest $order): void
    {
        // Update stock levels
        foreach ($order->getItems() as $item) {
            $this->stockService->finalizeStock($item);
        }
    }

    private function handleCompletedStatus(OrderRequest $order): void
    {
        $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
        // Additional completion logic (e.g., send confirmation email)
    }

    private function handleCancelledStatus(OrderRequest $order): void
    {
        // Release reserved stock
        foreach ($order->getItems() as $item) {
            $this->stockService->cancelReservation($item);
        }
        
        // Update payment status if needed
        if ($order->getPaymentStatus() === PaymentStatus::SUCCESSFUL->value) {
            $order->setPaymentStatus(PaymentStatus::REFUNDED->value);
        }
    }
}