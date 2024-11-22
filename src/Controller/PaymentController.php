<?php

namespace App\Controller;

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
}