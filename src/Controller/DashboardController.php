<?php

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use App\Repository\MovementRepository;
use App\Repository\OrderRequestRepository;
use App\Enum\EquipmentStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 * @package App\Controller
 */
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        Request $request,
        EquipmentRepository $equipmentRepository,
        CategoryRepository $categoryRepository,
        MovementRepository $movementRepository,
        OrderRequestRepository $orderRepository,
        AlertRepository $alertRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        // Handle filters and search
        $searchQuery = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', '');
        $categoryFilter = $request->query->get('category', '');
        $dateFilter = $request->query->get('date', '');

        // Get order statistics
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime();
        
        $monthlyOrders = $orderRepository->findByDateRange($startDate, $endDate);
        
        // Calculate total amount sale (monthly)
        $totalAmountSale = 0;
        foreach ($monthlyOrders as $order) {
            /** @var OrderRequest $order */
            $price = $order->getTotalPrice();
            dump("Order ID: " . $order->getId() . ", Price: " . $price);
            if ($price > 0) {
                $totalAmountSale += $price;
            }
        }
        dump("Total Monthly Amount: " . $totalAmountSale);

        // Calculate total amount weekly sale
        $weekStartDate = new \DateTime('-7 days');
        $weeklyOrders = $orderRepository->findByDateRange($weekStartDate, $endDate);
        $totalAmountWeeklySale = 0;
        foreach ($weeklyOrders as $order) {
            /** @var OrderRequest $order */
            $price = $order->getTotalPrice();
            dump("Weekly Order ID: " . $order->getId() . ", Price: " . $price);
            if ($price > 0) {
                $totalAmountWeeklySale += $price;
            }
        }
        dump("Total Weekly Amount: " . $totalAmountWeeklySale);

        $pendingOrders = $orderRepository->findPendingOrders();
        $orderStats = [
            'pendingOrders' => count($pendingOrders),
            'monthlyOrders' => count($monthlyOrders),
            'urgentOrders' => count(array_filter($pendingOrders, fn($o) => $o->getPriority() === 'high' || $o->getPriority() === 'urgent')),
            'averageProcessingTime' => $this->calculateAverageProcessingTime($monthlyOrders)
        ];

        // Debug monthly orders
        // Debug weekly orders

        // Calculate dashboard statistics
        $allEquipment = $equipmentRepository->findAll();
        $stats = [
            'totalEquipment' => count($allEquipment),
            'activeEquipment' => count(array_filter($allEquipment, fn($e) => $e->getStatus() === EquipmentStatus::ACTIVE)),
            'lowStockCount' => count($equipmentRepository->findLowStockItems()),
            'pendingOrders' => $orderStats['pendingOrders'],
            'totalStockValue' => $this->calculateTotalStockValue($allEquipment),
        ];

        // Get filtered equipment if search/filters are applied
        if (!empty($searchQuery) || !empty($statusFilter) || !empty($categoryFilter) || !empty($dateFilter)) {
            $filteredEquipment = $equipmentRepository->findFilteredEquipment($searchQuery, $statusFilter, $categoryFilter, $dateFilter);
        }

        // Get movement data for weekly chart
        $weeklyMovements = $movementRepository->findLastSevenDaysMovements();
        $movementDates = [];
        $movementDataIn = [];
        $movementDataOut = [];
        
        foreach ($weeklyMovements as $date => $data) {
            $movementDates[] = (new \DateTime($date))->format('d/m');
            $movementDataIn[] = $data['in'] ?? 0;
            $movementDataOut[] = $data['out'] ?? 0;
        }

        // Get movement data for monthly chart
        $monthlyMovements = $movementRepository->findLastMonthMovements();
        $monthlyMovementDates = [];
        $monthlyMovementDataIn = [];
        $monthlyMovementDataOut = [];
        
        foreach ($monthlyMovements as $date => $data) {
            $monthlyMovementDates[] = (new \DateTime($date))->format('d/m');
            $monthlyMovementDataIn[] = $data['in'] ?? 0;
            $monthlyMovementDataOut[] = $data['out'] ?? 0;
        }

        // Prepare order volume data for analytics chart
        $startDate = new \DateTime('-30 days');
        $endDate = new \DateTime();
        $orderVolumeData = $orderRepository->getOrderVolumeByDateRange($startDate, $endDate);

        // Calculate trend indicators
        $totalOrders = array_sum($orderVolumeData['total']);
        $completedOrders = array_sum($orderVolumeData['completed']);
        $pendingOrders = array_sum($orderVolumeData['pending']);
        $processingOrders = array_sum($orderVolumeData['processing']);
        
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        // Get category distribution data
        $categories = $categoryRepository->findAll();
        $categoryLabels = [];
        $categoryData = [];
        
        foreach ($categories as $category) {
            $categoryLabels[] = $category->getName();
            $categoryData[] = count($category->getEquipmentItems());
        }

        // Get recent activities
        $page = $request->query->getInt('page', 1);
        $limit = 3; // Number of items per page

        $recentActivities = $movementRepository->findPaginatedMovements($page, $limit);
        $totalActivities = $movementRepository->countMovements();
        $totalPages = ceil($totalActivities / $limit);

        $pagination = [
            'currentPage' => $page,
            'pageCount' => $totalPages,
            'totalItems' => $totalActivities,
            'itemsPerPage' => $limit,
        ];

        // Get active alerts
        $alerts = $alertRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        // Return JSON response for AJAX requests
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'stats' => $stats,
                'orderStats' => $orderStats,
                'movementDates' => $movementDates,
                'movementDataIn' => $movementDataIn,
                'movementDataOut' => $movementDataOut,
                'monthlyMovementDates' => $monthlyMovementDates,
                'monthlyMovementDataIn' => $monthlyMovementDataIn,
                'monthlyMovementDataOut' => $monthlyMovementDataOut,
                'orderVolumeData' => $orderVolumeData,
                'completionRate' => $completionRate,
            ]);
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'orderStats' => $orderStats,
            'movementDates' => $movementDates,
            'movementDataIn' => $movementDataIn,
            'movementDataOut' => $movementDataOut,
            'monthlyMovementDates' => $monthlyMovementDates,
            'monthlyMovementDataIn' => $monthlyMovementDataIn,
            'monthlyMovementDataOut' => $monthlyMovementDataOut,
            'categoryLabels' => $categoryLabels,
            'categoryData' => $categoryData,
            'orderVolumeData' => $orderVolumeData,
            'completionRate' => $completionRate,
            'recentActivities' => $recentActivities,
            'pagination' => $pagination,
            'alerts' => $alerts,
            'totalAmountSale' => $totalAmountSale,
            'totalAmountWeeklySale' => $totalAmountWeeklySale
        ]);
    }

    #[Route('/dashboard/orders/update', name: 'app_dashboard_orders_update', methods: ['GET'])]
    public function updateOrderData(OrderRequestRepository $orderRepository): JsonResponse
    {
        // Fetch the latest order statistics
        $orderStats = $orderRepository->getOrderStatistics();

        // Return the data as a JSON response
        return new JsonResponse([
            'total' => $orderStats['total'],
            'pending' => $orderStats['pending'],
            'processing' => $orderStats['processing'],
            'completed' => $orderStats['completed'],
        ]);
    }

    private function calculateTotalStockValue(array $equipment): float
    {
        return array_reduce($equipment, function($total, $item) {
            $price = $item->getPrice() ?? 0.0;
            $quantity = $item->getStockQuantity();
            return $total + ($quantity * $price);
        }, 0.0);
    }

    private function calculateAverageProcessingTime(array $orders): float
    {
        $processedOrders = array_filter($orders, fn($o) => $o->getStatus() === 'completed' && $o->getCompletedAt() !== null);
        if (empty($processedOrders)) {
            return 0;
        }

        $totalHours = array_reduce($processedOrders, function($total, $order) {
            $diff = $order->getCompletedAt()->diff($order->getOrderDate());
            return $total + ($diff->days * 24 + $diff->h);
        }, 0);

        return round($totalHours / count($processedOrders), 2);
    }
}