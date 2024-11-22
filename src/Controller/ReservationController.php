<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(ReservationRepository $repository): Response
    {
        // Récupère toutes les réservations
        $reservations = $repository->findBy([], ['reservationDate' => 'DESC']);
        return $this->render('reservation/index.html.twig', [
            // Liste all reservations
            'reservations' => $reservations
            
        ]);
    }
}
