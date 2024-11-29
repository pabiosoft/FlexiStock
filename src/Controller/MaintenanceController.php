<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\MaintenanceRecord;
use App\Form\MaintenanceRecordType;
use App\Repository\MaintenanceRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/maintenance')]
class MaintenanceController extends AbstractController
{
    #[Route('/', name: 'app_maintenance_index', methods: ['GET'])]
    public function index(MaintenanceRecordRepository $maintenanceRecordRepository): Response
    {
        return $this->render('maintenance/index.html.twig', [
            'upcoming_maintenance' => $maintenanceRecordRepository->findUpcomingMaintenance(),
            'maintenance_records' => $maintenanceRecordRepository->findBy([], ['maintenanceDate' => 'DESC']),
        ]);
    }

    #[Route('/equipment/{id}', name: 'app_maintenance_equipment_show', methods: ['GET'])]
    public function showEquipmentMaintenance(Equipment $equipment, MaintenanceRecordRepository $maintenanceRecordRepository): Response
    {
        $maintenanceHistory = $maintenanceRecordRepository->findMaintenanceHistory($equipment->getId());
        $maintenanceStats = $maintenanceRecordRepository->findMaintenanceStats($equipment->getId());

        return $this->render('maintenance/equipment.html.twig', [
            'equipment' => $equipment,
            'maintenance_records' => $maintenanceHistory,
            'maintenance_stats' => $maintenanceStats,
        ]);
    }

    #[Route('/overdue', name: 'app_maintenance_overdue', methods: ['GET'])]
    public function showOverdueMaintenance(MaintenanceRecordRepository $maintenanceRecordRepository): Response
    {
        $overdueMaintenance = $maintenanceRecordRepository->findOverdueMaintenance();

        return $this->render('maintenance/overdue.html.twig', [
            'overdue_maintenance' => $overdueMaintenance,
        ]);
    }

    #[Route('/dashboard', name: 'app_maintenance_dashboard', methods: ['GET'])]
    public function dashboard(MaintenanceRecordRepository $maintenanceRecordRepository): Response
    {
        $upcomingMaintenance = $maintenanceRecordRepository->findUpcomingMaintenanceInDays(7);
        $overdueMaintenance = $maintenanceRecordRepository->findOverdueMaintenance();

        return $this->render('maintenance/dashboard.html.twig', [
            'upcoming_maintenance' => $upcomingMaintenance,
            'overdue_maintenance' => $overdueMaintenance,
        ]);
    }

    #[Route('/new/{equipment}', name: 'app_maintenance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Equipment $equipment, EntityManagerInterface $entityManager, MaintenanceRecordRepository $maintenanceRecordRepository): Response
    {
        $maintenanceRecord = new MaintenanceRecord();
        $maintenanceRecord->setEquipment($equipment);
        $maintenanceRecord->setPerformedBy($this->getUser());
        
        $form = $this->createForm(MaintenanceRecordType::class, $maintenanceRecord);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($maintenanceRecord);
            $equipment->addMaintenanceRecord($maintenanceRecord);
            $entityManager->flush();

            $this->addFlash('success', 'Maintenance record has been created successfully.');
            return $this->redirectToRoute('app_maintenance_equipment_show', ['id' => $equipment->getId()]);
        }

        return $this->render('maintenance/new.html.twig', [
            'maintenance_record' => $maintenanceRecord,
            'equipment' => $equipment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MaintenanceRecord $maintenanceRecord, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MaintenanceRecordType::class, $maintenanceRecord);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Maintenance record has been updated successfully.');
            return $this->redirectToRoute('app_maintenance_equipment_show', ['id' => $maintenanceRecord->getEquipment()->getId()]);
        }

        return $this->render('maintenance/edit.html.twig', [
            'maintenance_record' => $maintenanceRecord,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, MaintenanceRecord $maintenanceRecord, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$maintenanceRecord->getId(), $request->request->get('_token'))) {
            $equipmentId = $maintenanceRecord->getEquipment()->getId();
            $entityManager->remove($maintenanceRecord);
            $entityManager->flush();
            
            $this->addFlash('success', 'Maintenance record has been deleted successfully.');
        }

        return $this->redirectToRoute('app_maintenance_equipment_show', ['id' => $equipmentId]);
    }
}
