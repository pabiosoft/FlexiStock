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
        $equipment = $orderItem->getEquipment();
        $availableStock = $equipment->getStockQuantity() - $equipment->getReservedQuantity();

        return $orderItem->getQuantity() <= $availableStock;
    }

    /**
     * Reserves stock for the given order item.
     * 
     * @param OrderItem $orderItem The order item to reserve stock for
     */
    public function reserveStock(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();

        // Update reserved quantity without reducing stock directly
        $equipment->setReservedQuantity($equipment->getReservedQuantity() + $orderItem->getQuantity());
    }

    /**
     * Finalizes stock after an order is completed.
     * 
     * @param OrderItem $orderItem The order item to finalize stock for
     */
    public function finalizeStock(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();

        // Deduct reserved stock and reduce actual stock
        $equipment->setReservedQuantity($equipment->getReservedQuantity() - $orderItem->getQuantity());
        $equipment->setStockQuantity($equipment->getStockQuantity() - $orderItem->getQuantity());
    }

    /**
     * Cancels stock reservation for an order item.
     * 
     * @param OrderItem $orderItem The order item to cancel reservation for
     */
     public function cancelReservation(OrderItem $orderItem): void
    {
        $equipment = $orderItem->getEquipment();
        $equipment->setReservedQuantity($equipment->getReservedQuantity() - $orderItem->getQuantity());
    }
}
