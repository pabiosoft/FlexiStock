<?php

namespace App\Service;

use App\Entity\OrderItem;

class StockService
{
    /**
     * Checks if there's enough stock available for the given order item.
     * 
     * @param OrderItem $orderItem The order item to check stock for
     * @return bool True if enough stock is available, false otherwise
     */
    public function checkStock(OrderItem $orderItem): bool
    {
        // Get the equipment associated with the order item
        $equipment = $orderItem->getEquipment();

        // Calculate the available stock: total stock - reserved stock
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();

        // Return true if the requested quantity is less than or equal to available stock
        return $orderItem->getQuantity() <= $availableStock;
    }

    /**
     * Reserves stock for the given order item.
     * 
     * @param OrderItem $orderItem The order item to reserve stock for
     */
    public function reserveStock(OrderItem $orderItem): void
    {
        // Get the equipment associated with the order item
        $equipment = $orderItem->getEquipment();

        // Reserve the stock by increasing the reserved quantity
        $equipment->setReservedQuantity($equipment->getReservedQuantity() + $orderItem->getQuantity());

        // Optionally, reduce the available stock to reflect the reservation
        $equipment->setStockQuantity($equipment->getStockQuantity() - $orderItem->getQuantity());

        // Persist changes (this should be handled in the controller or service layer)
    }

    /**
     * Finalizes the stock after the order has been completed.
     * 
     * @param OrderItem $orderItem The order item to finalize stock for
     */
    public function finalizeStock(OrderItem $orderItem): void
    {
        // Get the equipment associated with the order item
        $equipment = $orderItem->getEquipment();

        // Remove the reservation (cancel the reservation made earlier)
        $equipment->setReservedQuantity($equipment->getReservedQuantity() - $orderItem->getQuantity());

        // Reduce the stock quantity to reflect the completed order
        $equipment->setStockQuantity($equipment->getStockQuantity() - $orderItem->getQuantity());
    }
}
