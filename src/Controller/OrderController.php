<?php

namespace App\Controller;

use App\Form\OrderType;
use App\Entity\Equipment;
use App\Entity\OrderItem;
use App\Entity\Reservation;
use App\Entity\OrderRequest;
use App\Service\OrderService;
use App\Service\StockService;
use App\Service\InvoiceService;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use App\Service\OrderValidationService;
use App\Service\OrderNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/order')]
class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrderService $orderService;
    private InvoiceService $invoiceService;
    private OrderValidationService $orderValidationService;
    private OrderNotificationService $orderNotificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        OrderService $orderService,
        InvoiceService $invoiceService,
        OrderValidationService $orderValidationService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
        $this->orderValidationService = $orderValidationService;
        $this->orderNotificationService = $orderNotificationService;
    }

    #[Route('/add-to-cart/{equipmentId}', name: 'order_add_to_cart')]
    public function addToCart(
        $equipmentId,
        SessionInterface $session,
        EquipmentRepository $equipmentRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $equipment = $equipmentRepository->find($equipmentId);

        if (!$equipment) {
            $this->addFlash('error', 'Equipment not found.');
            return $this->redirectToRoute('order_create');
        }

        if ($equipment->getStockQuantity() <= 0) {
            $this->addFlash('error', 'This item is out of stock.');
            return $this->redirectToRoute('order_create');
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
            if ($cart[$equipmentId]['quantity'] >= $equipment->getStockQuantity()) {
                $this->addFlash('error', 'Cannot add more items than available in stock.');
                return $this->redirectToRoute('order_create');
            }
            $cart[$equipmentId]['quantity'] += 1;
            $cart[$equipmentId]['totalPrice'] = $cart[$equipmentId]['quantity'] * $equipment->getPrice();
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Item added to cart!');
        return $this->redirectToRoute('order_create');
    }

    #[Route('/create', name: 'order_create')]
    public function create(
        Request $request,
        SessionInterface $session,
        StockService $stockService,
        EquipmentRepository $equipmentRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

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
                // Validate stock availability
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

                // Create reservations and update stock
                foreach ($orderRequest->getItems() as $item) {
                    $stockService->reserveStock($item);

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

                // Send notifications
                $this->orderNotificationService->sendOrderConfirmation($orderRequest);

                // Clear cart
                $session->remove('cart');
                
                $this->addFlash('success', 'Order placed successfully!');
                return $this->redirectToRoute('order_show', ['id' => $orderRequest->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('order/create.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'equipmentList' => $equipmentRepository->findAll(),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/remove-from-cart/{equipmentId}', name: 'order_remove_from_cart')]
    public function removeFromCart($equipmentId, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (isset($cart[$equipmentId])) {
            unset($cart[$equipmentId]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Item removed from cart.');
        }

        return $this->redirectToRoute('order_create');
    }

    #[Route('/update-cart-quantity/{equipmentId}', name: 'order_update_cart_quantity', methods: ['POST'])]
    public function updateCartQuantity(
        Request $request,
        $equipmentId,
        SessionInterface $session,
        EquipmentRepository $equipmentRepository
    ): Response {
        $quantity = $request->request->getInt('quantity');
        $cart = $session->get('cart', []);

        if (isset($cart[$equipmentId])) {
            $equipment = $equipmentRepository->find($equipmentId);
            
            if (!$equipment) {
                $this->addFlash('error', 'Equipment not found.');
                return $this->redirectToRoute('order_create');
            }

            if ($quantity <= 0) {
                unset($cart[$equipmentId]);
            } elseif ($quantity <= $equipment->getStockQuantity()) {
                $cart[$equipmentId]['quantity'] = $quantity;
                $cart[$equipmentId]['totalPrice'] = $quantity * $cart[$equipmentId]['unitPrice'];
            } else {
                $this->addFlash('error', 'Requested quantity exceeds available stock.');
            }

            $session->set('cart', $cart);
        }

        return $this->redirectToRoute('order_create');
    }

    #[Route('/{id}', name: 'order_show', requirements: ['id' => '\d+'])]
    public function show(OrderRequest $orderRequest): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $orderRequest,
        ]);
    }

    #[Route('/list', name: 'order_list')]
    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);
        $criteria = [];
        
        if ($status = $request->query->get('status')) {
            $criteria['status'] = $status;
        }

        if ($paymentStatus = $request->query->get('payment_status')) {
            $criteria['paymentStatus'] = $paymentStatus;
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        $orderRepository = $this->entityManager->getRepository(OrderRequest::class);
        
        if ($startDate && $endDate) {
            $orders = $orderRepository->findByDateRange(
                new \DateTime($startDate),
                new \DateTime($endDate),
                $criteria
            );
        } else {
            $qb = $orderRepository->createQueryBuilder('o')
                ->orderBy('o.orderDate', 'DESC');

            foreach ($criteria as $field => $value) {
                $qb->andWhere("o.$field = :$field")
                   ->setParameter($field, $value);
            }

            $totalItems = count($qb->getQuery()->getResult());
            
            $qb->setFirstResult(($page - 1) * $limit)
               ->setMaxResults($limit);

            $orders = $qb->getQuery()->getResult();
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $limit,
                'totalItems' => $totalItems ?? count($orders),
                'pageCount' => ceil(($totalItems ?? count($orders)) / $limit)
            ]
        ]);
    }

    #[Route('/{id}/validate', name: 'order_validate')]
    public function validateOrder(OrderRequest $order): Response
    {
        if ($this->orderValidationService->validateOrder($order)) {
            $this->addFlash('success', 'Order validated successfully.');
            $this->orderNotificationService->sendStatusUpdate($order);
        } else {
            $this->addFlash('error', 'Order validation failed.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/update-status/{newStatus}', name: 'order_update_status')]
    public function updateStatus(OrderRequest $order, string $newStatus): Response
    {
        if ($this->orderValidationService->updateOrderStatus($order, $newStatus)) {
            $this->addFlash('success', 'Order status updated successfully.');
            $this->orderNotificationService->sendStatusUpdate($order);
        } else {
            $this->addFlash('error', 'Invalid status transition.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/invoice', name: 'order_invoice')]
    public function generateInvoice(OrderRequest $orderRequest): Response
    {
        try {
            $pdfPath = $this->invoiceService->generateInvoice($orderRequest);
            return $this->file($pdfPath, 'invoice-' . $orderRequest->getId() . '.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate invoice: ' . $e->getMessage());
            return $this->redirectToRoute('order_show', ['id' => $orderRequest->getId()]);
        }
    }

    #[Route('/{id}/view-invoice', name: 'order_view_invoice')]
    public function viewInvoice(OrderRequest $orderRequest): Response
    {
        try {
            $pdfPath = $this->invoiceService->generateInvoice($orderRequest);
            return $this->file($pdfPath, 'invoice-' . $orderRequest->getId() . '.pdf', ResponseHeaderBag::DISPOSITION_INLINE);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to view invoice: ' . $e->getMessage());
            return $this->redirectToRoute('order_show', ['id' => $orderRequest->getId()]);
        }
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    public function delete(Request $request, OrderRequest $orderRequest): Response
    {
        if ($this->isCsrfTokenValid('delete' . $orderRequest->getId(), $request->request->get('_token'))) {
            try {
                // Release any reserved stock
                foreach ($orderRequest->getItems() as $item) {
                    $equipment = $item->getEquipment();
                    $equipment->setReservedQuantity($equipment->getReservedQuantity() - $item->getQuantity());
                }

                $this->entityManager->remove($orderRequest);
                $this->entityManager->flush();

                $this->addFlash('success', 'Order deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting order: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('order_list');
    }
}