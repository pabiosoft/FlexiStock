<?php

namespace App\Service;

use App\Entity\OrderRequest;
use App\Entity\OrderItem;
use App\Entity\Equipment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrderService
{
    private EntityManagerInterface $entityManager;
    private StockService $stockService;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, StockService $stockService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->stockService = $stockService;
        $this->logger = $logger;
    }

    public function createOrder(OrderRequest $order, array $items): OrderRequest
    {
        $this->entityManager->beginTransaction();

        try {
            foreach ($items as $itemData) {
                $equipment = $this->entityManager->getRepository(Equipment::class)->find($itemData['equipment_id']);
                if (!$equipment) {
                    throw new \Exception('Equipment not found with ID: ' . $itemData['equipment_id']);
                }

                if ($itemData['quantity'] <= 0) {
                    throw new \Exception('Invalid quantity for equipment: ' . $equipment->getName());
                }

                // Vérification de stock
                if (!$this->stockService->checkStock($equipment, $itemData['quantity'])) {
                    throw new \Exception(sprintf(
                        'Insufficient stock for %s. Requested: %d, Available: %d',
                        $equipment->getName(),
                        $itemData['quantity'],
                        $equipment->getStockQuantity() - $equipment->getReservedQuantity()
                    ));
                }

                $orderItem = new OrderItem();
                $orderItem->setEquipment($equipment);
                $orderItem->setQuantity($itemData['quantity']);
                $orderItem->setUnitPrice($equipment->getPrice());
                $order->addItem($orderItem);

                // Réserver le stock
                $this->stockService->reserveStock($orderItem);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Order created successfully', ['order_id' => $order->getId()]);
            return $order;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Order creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteOrder(OrderRequest $orderRequest): void
    {
        foreach ($orderRequest->getItems() as $orderItem) {
            $this->stockService->cancelReservation($orderItem);
        }

        $this->entityManager->remove($orderRequest);
        $this->entityManager->flush();

        $this->logger->info('Order deleted successfully', ['order_id' => $orderRequest->getId()]);
    }
}
