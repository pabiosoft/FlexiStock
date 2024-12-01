<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\CategoryRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_MANAGER')]
class ReservationController extends AbstractController
{
    private $entityManager;
    private $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route('/', name: 'app_reservation', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        // Get filter parameters
        $filters = [];
        
        // Status filter
        $status = $request->query->get('status');
        if ($status) {
            $filters['status'] = $status;
        }

        // Category filter
        $categoryId = $request->query->get('category');
        if ($categoryId) {
            $filters['category'] = $categoryId;
        }

        // Date range filter
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        if ($startDate && $endDate) {
            $filters['start_date'] = $startDate;
            $filters['end_date'] = $endDate;
        }

        // Get paginated reservations
        $reservations = $this->reservationRepository->getPaginatedReservations($page, $limit, $filters);

        // Get counts for each status
        $activeCount = $this->reservationRepository->countByStatus('active');
        $completedCount = $this->reservationRepository->countByStatus('completed');
        $cancelledCount = $this->reservationRepository->countByStatus('cancelled');

        // Get category statistics
        $categoryStats = $this->reservationRepository->getReservationsByCategory();

        // Get all categories for the filter dropdown
        $categories = $categoryRepository->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'page' => $page,
            'active_count' => $activeCount,
            'completed_count' => $completedCount,
            'cancelled_count' => $cancelledCount,
            'category_stats' => $categoryStats,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }

    #[Route('/create', name: 'app_reservation_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setUser($this->getUser());
            $reservation->setStatus('active');
            
            // Update equipment reserved quantity
            $equipment = $reservation->getEquipment();
            $equipment->setReservedQuantity(
                $equipment->getReservedQuantity() + $reservation->getReservedQuantity()
            );

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Reservation created successfully.');
            return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            try {
                if ($reservation->getStatus() !== 'reserved') {
                    throw new \LogicException('Only reserved reservations can be cancelled.');
                }

                $this->entityManager->beginTransaction();
                try {
                    // Update equipment reserved quantity
                    $equipment = $reservation->getEquipment();
                    $equipment->setReservedQuantity(
                        $equipment->getReservedQuantity() - $reservation->getReservedQuantity()
                    );

                    // Update reservation status
                    $reservation->setStatus('cancelled');
                    $reservation->setReturnDate(new \DateTime());

                    $this->entityManager->flush();
                    $this->entityManager->commit();

                    $this->addFlash('success', 'Reservation cancelled successfully.');
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error cancelling reservation: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_reservation_complete', methods: ['POST'])]
    public function complete(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('complete'.$reservation->getId(), $request->request->get('_token'))) {
            try {
                if ($reservation->getStatus() !== 'reserved') {
                    throw new \LogicException('Only reserved reservations can be completed.');
                }

                $this->entityManager->beginTransaction();
                try {
                    // Update equipment reserved quantity
                    $equipment = $reservation->getEquipment();
                    $equipment->setReservedQuantity(
                        $equipment->getReservedQuantity() - $reservation->getReservedQuantity()
                    );

                    // Update reservation status
                    $reservation->setStatus('completed');
                    $reservation->setReturnDate(new \DateTime());

                    $this->entityManager->flush();
                    $this->entityManager->commit();

                    $this->addFlash('success', 'Reservation marked as completed successfully.');
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error completing reservation: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation): Response
    {    // UPDATE THE QUANTITY OF THE EQUIPMENT
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Reservation updated successfully.');
            return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            try {
                // Check if reservation can be deleted (only allow deleting non-active reservations)
                if ($reservation->getStatus() === 'active') {
                    throw new \LogicException('Cannot delete an active reservation. Please cancel or complete it first.');
                }

                $this->entityManager->beginTransaction();
                try {
                    // Update equipment reserved quantity
                    $equipment = $reservation->getEquipment();
                    
                    // Only reduce reserved quantity if the reservation is still in 'reserved' status
                    if ($reservation->getStatus() === 'reserved') {
                        $equipment->setReservedQuantity(
                            $equipment->getReservedQuantity() - $reservation->getReservedQuantity()
                        );
                    }

                    $this->entityManager->remove($reservation);
                    $this->entityManager->flush();
                    $this->entityManager->commit();

                    $this->addFlash('success', 'Reservation deleted successfully.');
                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting reservation: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_reservation', [], Response::HTTP_SEE_OTHER);
    }
}