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
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Creates a new order along with its items, checking stock and updating it accordingly.
     *
     * @param OrderRequest $order The order entity
     * @param array $items The items to add to the order, each containing 'equipment_id' and 'quantity'
     * @return OrderRequest The created order entity
     * @throws \Exception if an error occurs during the order creation process
     */
    public function createOrder(OrderRequest $order, array $items): OrderRequest
    {
        $this->entityManager->beginTransaction(); // Begin transaction for safety

        try {
            foreach ($items as $itemData) {
                // Find the equipment based on the ID
                $equipment = $this->entityManager->getRepository(Equipment::class)->find($itemData['equipment_id']);
                if (!$equipment) {
                    throw new \Exception('Equipment not found with ID: ' . $itemData['equipment_id']);
                }

                // Validate quantity
                if ($itemData['quantity'] <= 0) {
                    throw new \Exception('Invalid quantity for equipment: ' . $equipment->getName());
                }

                // Check stock availability
                if ($equipment->getQuantity() < $itemData['quantity']) {
                    throw new \Exception(sprintf(
                        'Not enough stock for %s. Requested: %d, Available: %d',
                        $equipment->getName(),
                        $itemData['quantity'],
                        $equipment->getQuantity()
                    ));
                }

                // Create and add the order item
                $orderItem = new OrderItem();
                $orderItem->setEquipment($equipment);
                $orderItem->setQuantity($itemData['quantity']);
                $orderItem->setUnitPrice($equipment->getPrice());
                $order->addItem($orderItem);

                // Update the stock of the equipment
                $equipment->setQuantity($equipment->getQuantity() - $itemData['quantity']);
                $this->entityManager->persist($equipment);
            }

            // Persist the order and commit the transaction
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // Log the success of order creation
            $this->logger->info('Order created successfully', ['order_id' => $order->getId()]);
            return $order;
        } catch (\Exception $e) {
            $this->entityManager->rollback(); // Rollback on failure
            $this->logger->error('Failed to create order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing order. Additional business logic can be applied here.
     *
     * @param OrderRequest $order The order to be updated
     * @return OrderRequest The updated order entity
     */
    public function updateOrder(OrderRequest $order): OrderRequest
    {
        // Additional business logic can go here
        $this->entityManager->flush();
        $this->logger->info('Order updated successfully', ['order_id' => $order->getId()]);
        return $order;
    }

    /**
     * Deletes an order and restores the stock for each item in the order.
     *
     * @param OrderRequest $orderRequest The order to be deleted
     */
    public function deleteOrder(OrderRequest $orderRequest): void
    {
        // Restore the stock for each item in the order
        foreach ($orderRequest->getItems() as $orderItem) {
            $equipment = $orderItem->getEquipment();
            $equipment->setQuantity($equipment->getQuantity() + $orderItem->getQuantity()); // Restore stock
            $this->entityManager->persist($equipment); // Persist the restored stock
        }

        // Delete the order
        $this->entityManager->remove($orderRequest);
        $this->entityManager->flush();

        // Log the success of order deletion and stock restoration
        $this->logger->info('Order deleted and stock restored successfully', ['order_id' => $orderRequest->getId()]);
    }

    /**
     * Calculates the total price of the order based on its items.
     *
     * @param OrderRequest $orderRequest The order to calculate the total price for
     * @return float The total price of the order
     */
    public function getOrderTotal(OrderRequest $orderRequest): float
    {
        $total = 0;
        foreach ($orderRequest->getItems() as $item) {
            $total += $item->getQuantity() * $item->getUnitPrice();
        }
        return $total;
    }
}
