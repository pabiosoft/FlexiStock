<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ReservationRepository $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route('/', name: 'app_reservation')]
    public function index(Request $request): Response
    {
        $criteria = [];

        // Apply filters if provided
        if ($status = $request->query->get('status')) {
            $criteria['status'] = $status;
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if ($startDate && $endDate) {
            $reservations = $this->reservationRepository->findByDateRange(
                new \DateTime($startDate),
                new \DateTime($endDate),
                $criteria
            );
        } else {
            $reservations = $this->reservationRepository->findBy(
                $criteria,
                ['reservationDate' => 'DESC']
            );
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/{id}/complete', name: 'reservation_complete', methods: ['POST'])]
    public function complete(Reservation $reservation): Response
    {
        if ($reservation->getStatus() !== 'reserved') {
            $this->addFlash('error', 'Only reserved items can be completed.');
            return $this->redirectToRoute('app_reservation');
        }

        $reservation->setStatus('completed');
        $reservation->setReturnDate(new \DateTime());

        $this->entityManager->flush();
        $this->addFlash('success', 'Reservation completed successfully.');

        return $this->redirectToRoute('app_reservation');
    }

    #[Route('/{id}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancel(Reservation $reservation): Response
    {
        if ($reservation->getStatus() !== 'reserved') {
            $this->addFlash('error', 'Only reserved items can be cancelled.');
            return $this->redirectToRoute('app_reservation');
        }

        $reservation->setStatus('cancelled');
        $reservation->setReturnDate(new \DateTime());

        // Update equipment reserved quantity
        $equipment = $reservation->getEquipment();
        $equipment->setReservedQuantity(
            $equipment->getReservedQuantity() - $reservation->getReservedQuantity()
        );

        $this->entityManager->flush();
        $this->addFlash('success', 'Reservation cancelled successfully.');

        return $this->redirectToRoute('app_reservation');
    }
}