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
use App\Repository\OrderRequestRepository;
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
                'equipment_id' => $equipmentId,
                'name' => $equipment->getName(),
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
        CategoryRepository $categoryRepository,
        OrderRequestRepository $orderRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);
        

        $orderRequest = new OrderRequest();
        $cart = $session->get('cart', []);

        foreach ($cart as $equipmentId => $details) {
            $equipment = $this->entityManager->getRepository(Equipment::class)->find($details['equipment_id']);
            if (!$equipment) {
                $this->addFlash('error', 'Equipment not found: ' . $details['name']);
                continue;
            }
            $orderItem = new OrderItem();
            $orderItem->setEquipment($equipment);
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

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 6);

        $criteria = [];
        if ($request->query->has('category')) {
            $criteria['category'] = $request->query->get('category');
        }

        $equipmentData = $equipmentRepository->getPaginatedEquipment($page, $limit, $criteria);
        $equipmentList = $equipmentData['items'];
        $totalItems = $equipmentData['totalItems'];
        $totalPages = $equipmentData['pageCount'];

        return $this->render('order/create.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'equipmentList' => $equipmentList,
            'categories' => $categoryRepository->findAll(),
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $limit,
                'totalItems' => $totalItems,
                'pageCount' => $totalPages
            ]
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
        $limit = $request->query->getInt('limit', 10);
        $criteria = [];
        
        // Add status filter
        if ($status = $request->query->get('status')) {
            $criteria['status'] = $status;
        }

        // Add search filter
        if ($search = $request->query->get('search')) {
            $criteria['search'] = $search;
        }

        $orderRepository = $this->entityManager->getRepository(OrderRequest::class);
        
        // Get order statistics with a single optimized query
        $statsResult = $orderRepository->createQueryBuilder('o')
            ->select('
                COUNT(o.id) as total_orders,
                SUM(CASE WHEN o.status = :pending THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN o.status = :completed THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN o.status = :cancelled THEN 1 ELSE 0 END) as cancelled_orders,
                COALESCE(SUM(o.totalPrice), 0) as total_amount
            ')
            ->setParameter('pending', 'pending')
            ->setParameter('completed', 'completed')
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getOneOrNullResult();

        $stats = [
            'total_orders' => $statsResult['total_orders'] ?? 0,
            'pending_orders' => $statsResult['pending_orders'] ?? 0,
            'completed_orders' => $statsResult['completed_orders'] ?? 0,
            'cancelled_orders' => $statsResult['cancelled_orders'] ?? 0,
            'total_amount' => $statsResult['total_amount'] ?? 0,
        ];

        // Main query for listing orders
        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->orderBy('o.orderDate', 'DESC');

        // Apply search filter
        if (isset($criteria['search'])) {
            $queryBuilder->andWhere('o.id LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
                ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        // Apply status filter
        if (isset($criteria['status'])) {
            $queryBuilder->andWhere('o.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Get total items
        $totalItems = count($queryBuilder->getQuery()->getResult());
        
        // Add pagination
        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $orders = $queryBuilder->getQuery()->getResult();
        $pageCount = ceil($totalItems / $limit);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'stats' => $stats,
            'pagination' => [
                'currentPage' => $page,
                'itemsPerPage' => $limit,
                'totalItems' => $totalItems,
                'pageCount' => $pageCount
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
    public function delete(Request $request, OrderRequest $order): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            try {
                $this->orderService->deleteOrder($order);
                $this->addFlash('success', sprintf('Order #%d deleted successfully.', $order->getId()));
            } catch (\LogicException $e) {
                // Business logic exceptions (e.g., trying to delete active order)
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                // Unexpected errors
                $this->addFlash('error', 'An unexpected error occurred while deleting the order.');
                $this->logger->error('Unexpected error during order deletion', [
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->redirectToRoute('order_list', [], Response::HTTP_SEE_OTHER);
    }
}