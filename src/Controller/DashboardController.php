<?php

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use App\Repository\MovementRepository;
use App\Repository\OrderRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        // Calculate dashboard statistics
        $stats = [
            'totalEquipment' => $equipmentRepository->count([]),
            'activeEquipment' => $equipmentRepository->count(['status' => 'active']),
            'lowStockCount' => count($equipmentRepository->findLowStockItems()),
            'pendingOrders' => $orderRepository->count(['status' => 'pending']),
            'totalStockValue' => $this->calculateTotalStockValue($equipmentRepository),
        ];

        // Get movement data for the chart
        $movements = $movementRepository->findLastSevenDaysMovements();
        $movementDates = [];
        $movementDataIn = [];
        $movementDataOut = [];
        
        foreach ($movements as $date => $data) {
            $movementDates[] = $date;
            $movementDataIn[] = $data['in'] ?? 0;
            $movementDataOut[] = $data['out'] ?? 0;
        }

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

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'movementDates' => $movementDates,
            'movementDataIn' => $movementDataIn,
            'movementDataOut' => $movementDataOut,
            'categoryLabels' => $categoryLabels,
            'categoryData' => $categoryData,
            'recentActivities' => $recentActivities,
            'pagination' => $pagination,
            'alerts' => $alerts,
        ]);
    }

    private function calculateTotalStockValue(EquipmentRepository $repository): float
    {
        $total = 0;
        $equipment = $repository->findAll();
        
        foreach ($equipment as $item) {
            $total += $item->getStockQuantity() * ($item->getPrice() ?? 0);
        }
        
        return $total;
    }
}