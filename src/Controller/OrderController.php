<?php

namespace App\Controller;

use App\Form\OrderType;
use App\Entity\Equipment;
use App\Entity\OrderItem;
use App\Entity\OrderRequest;
use App\Service\OrderService;
use App\Service\StockService;
use App\Service\InvoiceService;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Reservation; 
use App\Service\OrderValidationService;
// Add this line to import the Reservation class

class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrderService $orderService;
    private InvoiceService $invoiceService;
    private OrderValidationService $orderValidationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderService $orderService,
        InvoiceService $invoiceService,
        OrderValidationService $orderValidationService

    ) {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
        $this->orderValidationService = $orderValidationService;
    }

    // Add to cart
   // Add to cart
   #[Route('/order/add-to-cart/{equipmentId}', name: 'order_add_to_cart')]
   public function addToCart(
       $equipmentId,
       SessionInterface $session,
       EquipmentRepository $equipmentRepository
   ): Response {
       $equipment = $equipmentRepository->find($equipmentId);

       if (!$equipment) {
           throw $this->createNotFoundException('Equipment not found.');
       }

       $cart = $session->get('cart', []);
       if (!isset($cart[$equipmentId])) {
           $cart[$equipmentId] = [
               'equipment' => $equipment,
               'quantity' => 1,
               'unitPrice' => $equipment->getPrice(),
               'totalPrice' => $equipment->getPrice(),
           ];
       } else {
           $cart[$equipmentId]['quantity'] += 1;
           $cart[$equipmentId]['totalPrice'] += $equipment->getPrice();
       }

       $session->set('cart', $cart);
       $this->addFlash('success', 'Item added to cart!');
       return $this->redirectToRoute('order_create');
   }

   // Create order
   #[Route('/order/create', name: 'order_create')]
   public function create(
       Request $request,
       SessionInterface $session,
       StockService $stockService,
       EquipmentRepository $equipmentRepository
   ): Response {
       $orderRequest = new OrderRequest();
       $cart = $session->get('cart', []);

       foreach ($cart as $equipmentId => $details) {
           $orderItem = new OrderItem();
           $orderItem->setEquipment($this->entityManager->getRepository(Equipment::class)->find($equipmentId));
           $orderItem->setQuantity($details['quantity']);
           $orderItem->setUnitPrice($details['unitPrice']);
           $orderRequest->addItem($orderItem);
       }

       $form = $this->createForm(OrderType::class, $orderRequest);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           try {
               $insufficientItems = [];
               foreach ($orderRequest->getItems() as $item) {
                   if (!$stockService->checkStock($item)) {
                       $insufficientItems[] = sprintf(
                           '%s (Requested: %d, Available: %d)',
                           $item->getEquipment()->getName(),
                           $item->getQuantity(),
                           $item->getEquipment()->getStockQuantity()
                       );
                   }
               }

               if (!empty($insufficientItems)) {
                   $this->addFlash('error', 'Insufficient stock for: ' . implode(', ', $insufficientItems));
                   return $this->redirectToRoute('order_create');
               }

               foreach ($orderRequest->getItems() as $item) {
                   $stockService->reserveStock($item);

                   // Create a reservation entry
                   $reservation = new Reservation();
                   $reservation->setEquipment($item->getEquipment());
                   $reservation->setStatus('reserved');
                   $reservation->setReservationDate(new \DateTime());
                   $reservation->setReservedQuantity($item->getQuantity());

                   $this->entityManager->persist($reservation);
               }

               $orderRequest->setStatus('pending');
               $this->entityManager->persist($orderRequest);
               $this->entityManager->flush();

               $this->addFlash('success', 'Order placed successfully!');
               $session->remove('cart');
               return $this->redirectToRoute('order_show', ['id' => $orderRequest->getId()]);
           } catch (\Exception $e) {
               $this->addFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
           }
       }

       $equipmentList = $equipmentRepository->findAll();

       return $this->render('order/create.html.twig', [
           'form' => $form->createView(),
           'cart' => $cart,
           'equipmentList' => $equipmentList,
       ]);
   }
    // Remove from cart
    #[Route('/order/remove-from-cart/{equipmentId}', name: 'order_remove_from_cart')]
    public function removeFromCart($equipmentId, SessionInterface $session): Response {
        $cart = $session->get('cart', []);
        if (isset($cart[$equipmentId])) {
            unset($cart[$equipmentId]);
            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('order_create');
    }

    // Show order
    #[Route('/order/{id}', name: 'order_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('The order does not exist');
        }

        return $this->render('order/show.html.twig', [
            'order' => $orderRequest,
        ]);
    }

    // List all orders
    #[Route('/order/list', name: 'order_list')]
    public function list(): Response {
        $orders = $this->entityManager->getRepository(OrderRequest::class)->findAll();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    // // Validate order
    // #[Route('/order/{id}/validate', name: 'order_validate')]
    // public function validateOrder(int $id): Response {
    //     $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

    //     if (!$orderRequest) {
    //         throw $this->createNotFoundException('Order not found.');
    //     }

    //     $orderRequest->setStatus('validated');
    //     $this->entityManager->flush();

    //     $this->addFlash('success', 'Order validated.');
    //     return $this->redirectToRoute('order_list');
    // }

    // Cancel order
    #[Route('/order/{id}/cancel', name: 'order_cancel')]
    public function cancelOrder(int $id): Response {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('Order not found.');
        }

        $orderRequest->setStatus('cancelled');
        $this->entityManager->flush();

        $this->addFlash('success', 'Order cancelled.');
        return $this->redirectToRoute('order_list');
    }

    // Generate invoice
    #[Route('/order/{id}/invoice', name: 'order_invoice')]
    public function generateInvoice(int $id): Response {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('Order not found.');
        }

        try {
            $pdfPath = $this->invoiceService->generateInvoice($orderRequest);
            $this->addFlash('success', 'Invoice generated successfully.');

            return $this->file($pdfPath, 'invoice-' . $orderRequest->getId() . '.pdf');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate invoice: ' . $e->getMessage());
            return $this->redirectToRoute('order_show', ['id' => $id]);
        }
    }

    // Delete order
    #[Route('/order/{id}/delete', name: 'order_delete', requirements: ['id' => '\d+'])]
    public function delete(int $id): Response {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('The order does not exist');
        }

        $this->entityManager->remove($orderRequest);
        $this->entityManager->flush();

        $this->addFlash('success', 'Order deleted successfully.');
        return $this->redirectToRoute('order_list');
    }
     #[Route('/order/{id}/view-invoice', name: 'order_view_invoice', requirements: ['id' => '\d+'])]
    public function viewInvoice(OrderRequest $orderRequest): Response
    {
        // Path to the invoice PDF file
        $invoiceFile = $this->invoiceService->generateInvoice($orderRequest);

        // Check if the file exists
        if (!file_exists($invoiceFile)) {
            throw $this->createNotFoundException('Invoice not found.');
        }

        // Serve the PDF file as a response
        return $this->file($invoiceFile, 'invoice-' . $orderRequest->getId() . '.pdf', ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/order/validate/{id}', name: 'order_validate')]
    public function validateOrder(OrderRequest $order): Response
    {
        if ($this->orderValidationService->validateOrder($order)) {
            $this->addFlash('success', 'Order validated successfully.');
        } else {
            $this->addFlash('error', 'Order validation failed.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/order/{id}/update-status/{newStatus}', name: 'order_update_status')]
    public function updateStatus(OrderRequest $order, string $newStatus): Response
    {
        if ($this->orderValidationService->updateOrderStatus($order, $newStatus)) {
            $this->addFlash('success', 'Order status updated successfully.');
        } else {
            $this->addFlash('error', 'Invalid status transition.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }
}
