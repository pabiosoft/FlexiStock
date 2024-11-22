<?php

namespace App\Service;

use App\Entity\OrderRequest;
use App\Enum\PaymentStatus;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentService
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function initializePayment(OrderRequest $order, string $paymentMethod): bool
    {
        // Vérifier si la commande peut être payée
        if (!$this->canProcessPayment($order)) {
            return false;
        }
       
        $order->setPaymentMethod($paymentMethod); // Store the payment method
    //     SI LE MEETHOSE DE PAYMENT EST CASH ON DELIVERY
    // PAYMENT STAUS = CONFIRMED
    
        $order->setPaymentStatus(PaymentStatus::PROCESSING->value);
        $this->entityManager->flush();
        

        // Simuler le processus de paiement selon la méthode
        return match($paymentMethod) {
            'card' => $this->processCardPayment($order),
            'bank_transfer' => $this->processBankTransfer($order),
            'cash_on_delivery' => $this->processCashOnDelivery($order),
            'other' => $this->processOtherPayment($order),
            default => false,
        };
    }

    private function canProcessPayment(OrderRequest $order): bool
    {
        // Vérifier si la commande est dans un état permettant le paiement
        if (!in_array($order->getStatus(), [OrderStatus::PENDING->value, OrderStatus::VALIDATED->value])) {
            return false;
        }

        // Vérifier si le payment method est approprié
        // if ($order->$order->getPaymentMethod() === 'cash_on_delivery') {
        //     return 
        // }

        // Vérifier si le paiement n'est pas déjà traité
        if ($order->getPaymentStatus() === PaymentStatus::SUCCESSFUL->value) {
            return false;
        }

        return true;
    }

    private function processCardPayment(OrderRequest $order): bool
    {
        try {
            // Simulation d'un paiement par carte
            $success = random_int(0, 10) > 2; // 80% de chance de succès

            if ($success) {
                $this->markPaymentSuccessful($order);
            } else {
                $this->markPaymentFailed($order, 'Card payment failed');
            }

            return $success;
        } catch (\Exception $e) {
            $this->markPaymentFailed($order, $e->getMessage());
            return false;
        }
    }

    private function processBankTransfer(OrderRequest $order): bool
    {
        // Marquer comme en attente de vérification
        $order->setPaymentStatus(PaymentStatus::PROCESSING->value);
        $this->entityManager->flush();

        return true;
    }

    private function processCashOnDelivery(OrderRequest $order): bool
    {
        // Marquer comme en attente de livraison
        $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
        $this->entityManager->flush();

        return true;
    }

    private function processOtherPayment(OrderRequest $order): bool
    {
        // Marquer comme payé
        $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
        $order->setPaymentMethod('other'); // Store 'other' as the payment method

        // Enregistrer le paiement en autre
        // $this->entityManager->flush();


        return true;
    }

    public function markPaymentSuccessful(OrderRequest $order): void
    {
        $order->setPaymentStatus(PaymentStatus::SUCCESSFUL->value);
        $order->setStatus(OrderStatus::VALIDATED->value);
        $this->entityManager->flush();

        $session = $this->requestStack->getSession();
        // $session->getFlashBag()->add('success', 'Payment processed successfully');
    }

    public function markPaymentFailed(OrderRequest $order, string $reason): void
    {
        $order->setPaymentStatus(PaymentStatus::FAILED->value);
        $order->setPaymentMethod(null); // Clear payment method on failure
        $this->entityManager->flush();

        $session = $this->requestStack->getSession();
        // $session->getFlashBag()->add('error', 'Payment failed: ' . $reason);
    }

    public function processRefund(OrderRequest $order): bool
    {
        if (!$this->canRefund($order)) {
            return false;
        }

        try {
            // Simuler le processus de remboursement
            $success = random_int(0, 10) > 1; // 90% de chance de succès

            if ($success) {
                $order->setPaymentStatus(PaymentStatus::REFUNDED->value);
                $order->setStatus(OrderStatus::REFUNDED->value);
                $this->entityManager->flush();

                $session = $this->requestStack->getSession();
                // $session->getFlashBag()->add('success', 'Refund processed successfully');
            }

            return $success;
        } catch (\Exception $e) {
            $session = $this->requestStack->getSession();
            // $session->getFlashBag()->add('error', 'Refund failed: ' . $e->getMessage());
            return false;
        }
    }

    private function canRefund(OrderRequest $order): bool
    {
        return in_array($order->getStatus(), [
            OrderStatus::COMPLETED->value,
            OrderStatus::CANCELLED->value
        ]) && $order->getPaymentStatus() === PaymentStatus::SUCCESSFUL->value;
    }
}
