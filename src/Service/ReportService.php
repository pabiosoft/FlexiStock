<?php

namespace App\Service;

use App\Repository\EquipmentRepository;
use App\Repository\MovementRepository;
use App\Repository\OrderRequestRepository;
use App\Repository\SupplierRepository;
use Dompdf\Dompdf;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class ReportService
{
    private EquipmentRepository $equipmentRepository;
    private MovementRepository $movementRepository;
    private OrderRequestRepository $orderRepository;
    private SupplierRepository $supplierRepository;
    private Environment $twig;
    private KernelInterface $kernel;

    public function __construct(
        EquipmentRepository $equipmentRepository,
        MovementRepository $movementRepository,
        OrderRequestRepository $orderRepository,
        SupplierRepository $supplierRepository,
        Environment $twig,
        KernelInterface $kernel
    ) {
        $this->equipmentRepository = $equipmentRepository;
        $this->movementRepository = $movementRepository;
        $this->orderRepository = $orderRepository;
        $this->supplierRepository = $supplierRepository;
        $this->twig = $twig;
        $this->kernel = $kernel;
    }

    public function getAvailableReportTypes(): array
    {
        return [
            'inventory' => 'Current Inventory Status',
            'movements' => 'Stock Movements',
            'low_stock' => 'Low Stock Items',
            'valuation' => 'Stock Valuation',
            'expiring' => 'Expiring Items',
            'orders' => 'Order History',
            'financial' => 'Financial Analysis',
            'trends' => 'Stock Trends',
            'supplier' => 'Supplier Analysis',
            'category' => 'Category Analysis',
            'performance' => 'Performance Analytics',
            'forecast' => 'Inventory Forecast',
            'efficiency' => 'Operational Efficiency',
            'audit' => 'Stock Audit Report'
        ];
    }

    public function generateReport(string $type, \DateTime $startDate, \DateTime $endDate): array
    {
        return match($type) {
            'inventory' => $this->generateInventoryReport(),
            'movements' => $this->generateMovementsReport($startDate, $endDate),
            'low_stock' => $this->generateLowStockReport(),
            'valuation' => $this->generateValuationReport(),
            'expiring' => $this->generateExpiringItemsReport(),
            'orders' => $this->generateOrdersReport($startDate, $endDate),
            'financial' => $this->generateFinancialReport($startDate, $endDate),
            'trends' => $this->generateTrendsReport($startDate, $endDate),
            'supplier' => $this->generateSupplierReport($startDate, $endDate),
            'category' => $this->generateCategoryReport(),
            'performance' => $this->generatePerformanceReport($startDate, $endDate),
            'forecast' => $this->generateForecastReport($startDate, $endDate),
            'efficiency' => $this->generateEfficiencyReport($startDate, $endDate),
            'audit' => $this->generateAuditReport($startDate, $endDate),
            default => throw new \InvalidArgumentException('Invalid report type')
        };
    }

    private function generateEfficiencyReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $report = [
            'metrics' => [
                'pickingAccuracy' => $this->calculatePickingAccuracy($startDate, $endDate),
                'orderProcessingTime' => $this->calculateAverageProcessingTime($startDate, $endDate),
                'stockoutRate' => $this->calculateStockoutRate($startDate, $endDate),
                'inventoryTurnover' => $this->calculateInventoryTurnover($startDate, $endDate)
            ],
            'trends' => [
                'daily' => $this->getEfficiencyTrends($startDate, $endDate, 'daily'),
                'weekly' => $this->getEfficiencyTrends($startDate, $endDate, 'weekly'),
                'monthly' => $this->getEfficiencyTrends($startDate, $endDate, 'monthly')
            ],
            'bottlenecks' => $this->identifyBottlenecks($startDate, $endDate),
            'recommendations' => $this->generateEfficiencyRecommendations()
        ];

        return $report;
    }

    private function generateAuditReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $report = [
            'summary' => [
                'totalItems' => $this->equipmentRepository->count([]),
                'discrepancies' => $this->findStockDiscrepancies(),
                'lastAuditDate' => $this->getLastAuditDate(),
                'accuracy' => $this->calculateInventoryAccuracy()
            ],
            'discrepancyDetails' => $this->getDetailedDiscrepancies(),
            'locationAccuracy' => $this->calculateLocationAccuracy(),
            'valueDiscrepancies' => $this->calculateValueDiscrepancies(),
            'recommendations' => $this->generateAuditRecommendations()
        ];

        return $report;
    }

    private function calculatePickingAccuracy(\DateTime $startDate, \DateTime $endDate): float
    {
        $orders = $this->orderRepository->findByDateRange($startDate, $endDate);
        $totalOrders = count($orders);
        $accurateOrders = 0;

        foreach ($orders as $order) {
            if ($this->isOrderPickingAccurate($order)) {
                $accurateOrders++;
            }
        }

        return $totalOrders > 0 ? ($accurateOrders / $totalOrders) * 100 : 0;
    }

    private function calculateInventoryAccuracy(): float
    {
        $equipment = $this->equipmentRepository->findAll();
        $totalItems = count($equipment);
        $accurateItems = 0;

        foreach ($equipment as $item) {
            if ($this->isInventoryCountAccurate($item)) {
                $accurateItems++;
            }
        }

        return $totalItems > 0 ? ($accurateItems / $totalItems) * 100 : 0;
    }

    private function findStockDiscrepancies(): array
    {
        $discrepancies = [];
        $equipment = $this->equipmentRepository->findAll();

        foreach ($equipment as $item) {
            $systemQuantity = $item->getStockQuantity();
            $actualQuantity = $this->getActualQuantity($item);

            if ($systemQuantity !== $actualQuantity) {
                $discrepancies[] = [
                    'item' => $item->getName(),
                    'systemQuantity' => $systemQuantity,
                    'actualQuantity' => $actualQuantity,
                    'difference' => $actualQuantity - $systemQuantity,
                    'value' => abs($actualQuantity - $systemQuantity) * $item->getPrice()
                ];
            }
        }

        return $discrepancies;
    }

    private function generateEfficiencyRecommendations(): array
    {
        $recommendations = [];
        $equipment = $this->equipmentRepository->findAll();

        foreach ($equipment as $item) {
            if ($this->shouldOptimizeLocation($item)) {
                $recommendations[] = [
                    'type' => 'location_optimization',
                    'item' => $item->getName(),
                    'current_location' => $item->getLocation(),
                    'suggested_location' => $this->suggestOptimalLocation($item),
                    'potential_improvement' => $this->calculateImprovementPotential($item)
                ];
            }

            if ($this->shouldAdjustReorderPoint($item)) {
                $recommendations[] = [
                    'type' => 'reorder_point_adjustment',
                    'item' => $item->getName(),
                    'current_point' => $item->getMinThreshold(),
                    'suggested_point' => $this->calculateOptimalReorderPoint($item),
                    'reason' => $this->getReorderPointAdjustmentReason($item)
                ];
            }
        }

        return $recommendations;
    }

    private function calculateImprovementPotential($item): array
    {
        return [
            'time_saved' => $this->estimateTimeSaving($item),
            'cost_reduction' => $this->estimateCostReduction($item),
            'error_reduction' => $this->estimateErrorReduction($item)
        ];
    }

    private function estimateTimeSaving($item): float
    {
        // Calculate potential time savings based on historical movement data
        $movements = $this->movementRepository->findBy(['equipment' => $item]);
        $currentAverageTime = $this->calculateAverageProcessingTime(new \DateTime('-30 days'), new \DateTime());
        $projectedTime = $this->calculateProjectedProcessingTime($item);
        
        return $currentAverageTime - $projectedTime;
    }

    private function estimateCostReduction($item): float
    {
        // Calculate potential cost savings
        $currentCosts = $this->calculateCurrentOperationalCosts($item);
        $projectedCosts = $this->calculateProjectedOperationalCosts($item);
        
        return $currentCosts - $projectedCosts;
    }

    private function estimateErrorReduction($item): float
    {
        // Calculate potential error reduction
        $currentErrorRate = $this->calculateCurrentErrorRate($item);
        $projectedErrorRate = $this->calculateProjectedErrorRate($item);
        
        return $currentErrorRate - $projectedErrorRate;
    }

    private function generatePDF(array $report, string $type): string
    {
        $html = $this->twig->render('report/pdf/' . $type . '.html.twig', [
            'report' => $report,
            'generatedAt' => new \DateTime()
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $filepath = $this->kernel->getProjectDir() . '/var/reports/' . $type . '-' . time() . '.pdf';
        file_put_contents($filepath, $output);

        return $filepath;
    }

    private function generateCSV(array $report, string $type): string
    {
        $filepath = $this->kernel->getProjectDir() . '/var/reports/' . $type . '-' . time() . '.csv';
        $handle = fopen($filepath, 'w');

        // Write headers
        fputcsv($handle, $this->getCSVHeaders($type));

        // Write data
        foreach ($this->getCSVData($report, $type) as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
        return $filepath;
    }

    private function getCSVHeaders(string $type): array
    {
        return match($type) {
            'inventory' => ['Item', 'Category', 'Quantity', 'Value', 'Status'],
            'financial' => ['Date', 'Revenue', 'Cost', 'Profit', 'Orders'],
            'supplier' => ['Supplier', 'Orders', 'Value', 'Performance', 'Status'],
            default => ['Date', 'Type', 'Value']
        };
    }

    private function getCSVData(array $report, string $type): array
    {
        return match($type) {
            'inventory' => $this->formatInventoryDataForCSV($report),
            'financial' => $this->formatFinancialDataForCSV($report),
            'supplier' => $this->formatSupplierDataForCSV($report),
            default => $this->formatGenericDataForCSV($report)
        };
    }
}