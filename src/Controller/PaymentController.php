<?php

namespace App\Controller;

use App\Enum\PaymentStatus;
use App\Entity\OrderRequest;
use App\Service\PaymentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function validateCashPayment(OrderRequest $order): Response
    {
        // Vérifier si le paiement peut être validé
        if ($order->getPaymentStatus() === PaymentStatus::PROCESSING->value) {
            $this->paymentService->markPaymentSuccessful($order);

            $this->addFlash('success', 'Cash payment validated successfully.');
        } else {
            $this->addFlash('error', 'Payment cannot be validated at this stage.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }
}
