<?php

namespace App\Controller;

use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/reports')]
class ReportController extends AbstractController
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    #[Route('/', name: 'report_index')]
    public function index(): Response
    {
        return $this->render('report/index.html.twig', [
            'reportTypes' => $this->reportService->getAvailableReportTypes()
        ]);
    }

    #[Route('/generate/{type}', name: 'report_generate')]
    public function generate(string $type, Request $request): Response
    {
        $startDate = new \DateTime($request->query->get('start_date', 'first day of this month'));
        $endDate = new \DateTime($request->query->get('end_date', 'last day of this month'));
        $format = $request->query->get('format', 'html');

        $report = $this->reportService->generateReport($type, $startDate, $endDate);

        if ($format === 'pdf') {
            $pdf = $this->reportService->generatePDF($report, $type);
            
            return $this->file($pdf, sprintf('report-%s.pdf', $type), ResponseHeaderBag::DISPOSITION_INLINE);
        }

        if ($format === 'csv') {
            $csv = $this->reportService->generateCSV($report, $type);
            
            return $this->file($csv, sprintf('report-%s.csv', $type), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }

        return $this->render('report/view.html.twig', [
            'report' => $report,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}