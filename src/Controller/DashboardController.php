<?php

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use App\Repository\MovementRepository;
use App\Repository\OrderRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        EquipmentRepository $equipmentRepository,
        CategoryRepository $categoryRepository,
        MovementRepository $movementRepository,
        OrderRequestRepository $orderRepository,
        AlertRepository $alertRepository
    ): Response {
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
        $recentActivities = $movementRepository->findBy(
            [],
            ['movementDate' => 'DESC'],
            10
        );

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