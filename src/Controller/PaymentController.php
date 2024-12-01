<?php

namespace App\Controller;

use App\Enum\PaymentStatus;
use App\Entity\OrderRequest;
use App\Service\PaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Enum\OrderStatus;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }


    #[Route('/process/{id}', name: 'payment_process')]
    public function processPayment(Request $request, OrderRequest $order): Response
    {
        $paymentMethod = $request->request->get('payment_method');

        if ($this->paymentService->initializePayment($order, $paymentMethod)) {
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $this->addFlash('error', 'Payment could not be processed');
        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }



    #[Route('/refund/{id}', name: 'payment_refund')]
    public function refundPayment(OrderRequest $order): Response
    {
        if ($this->paymentService->processRefund($order)) {
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $this->addFlash('error', 'Refund could not be processed');
        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }
    #[Route('/other/{id}', name: 'payment_other')]
    public function OtherPayment(OrderRequest $order): Response
    {


        $this->addFlash('error', 'Payment could not be processed');
        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/validate-cash/{id}', name: 'validate_cash_payment', methods: ['POST'])]
    public function validateCashPayment(Request $request, OrderRequest $order): Response
    {
        if ($this->isCsrfTokenValid('validate-cash'.$order->getId(), $request->request->get('_token'))) {
            try {
                // Check if order status allows payment validation
                if (!in_array($order->getStatus(), [OrderStatus::PENDING->value, OrderStatus::VALIDATED->value])) {
                    throw new \LogicException('Order is not in a valid state for payment validation.');
                }

                // Check if payment method is cash
                if ($order->getPaymentMethod() !== 'cash_on_delivery') {
                    throw new \LogicException('This order is not marked for cash payment.');
                }

                // Check if payment is in processing state
                if ($order->getPaymentStatus() !== PaymentStatus::PROCESSING->value) {
                    throw new \LogicException('Payment cannot be validated at this stage.');
                }

                $this->paymentService->markPaymentSuccessful($order);
                $this->addFlash('success', 'Cash payment validated successfully.');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'An unexpected error occurred while validating the payment.');
            }
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }
}
