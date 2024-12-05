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

        // Get essential statistics only
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime();
        $weekStartDate = new \DateTime('-7 days');
        
        // Get order stats with a single query
        $orderStats = $orderRepository->getOrderStatistics($startDate, $endDate);
        $weeklyStats = $orderRepository->getOrderStatistics($weekStartDate, $endDate);
        
        // Get equipment stats with optimized queries
        $equipmentStats = $equipmentRepository->getDashboardStats();
        
        // Get only essential movement data for charts
        $weeklyMovements = $movementRepository->findLastSevenDaysMovements();
        $movementDates = [];
        $movementDataIn = [];
        $movementDataOut = [];
        
        foreach ($weeklyMovements as $date => $data) {
            $movementDates[] = (new \DateTime($date))->format('d/m');
            $movementDataIn[] = $data['in'] ?? 0;
            $movementDataOut[] = $data['out'] ?? 0;
        }

        // Get recent movements (limit to 5)
        $recentMovements = $movementRepository->findRecentMovements(5);

        // Get recent alerts (limit to 5)
        $alerts = $alertRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'totalEquipment' => $equipmentStats['total'] ?? 0,
                'activeEquipment' => $equipmentStats['active'] ?? 0,
                'lowStockCount' => $equipmentStats['lowStock'] ?? 0,
                'pendingOrders' => $orderStats['pending'] ?? 0,
                'totalStockValue' => $equipmentStats['totalValue'] ?? 0,
            ],
            'orderStats' => [
                'pendingOrders' => $orderStats['pending'] ?? 0,
                'monthlyOrders' => $orderStats['total'] ?? 0,
                'totalAmount' => $orderStats['totalAmount'] ?? 0,
                'weeklyAmount' => $weeklyStats['totalAmount'] ?? 0
            ],
            'movementDates' => $movementDates,
            'movementDataIn' => $movementDataIn,
            'movementDataOut' => $movementDataOut,
            'alerts' => $alerts,
            'recentMovements' => $recentMovements
        ]);
    }

    #[Route('/dashboard/orders/update', name: 'app_dashboard_orders_update', methods: ['GET'])]
    public function updateOrderData(OrderRequestRepository $orderRepository): JsonResponse
    {
        // Set date range for the last 30 days
        $endDate = new \DateTime();
        $startDate = new \DateTime('-30 days');

        // Fetch the latest order statistics
        $orderStats = $orderRepository->getOrderStatistics($startDate, $endDate);

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