<?php

namespace App\Controller;

use App\Repository\EquipmentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart')]
    public function index(): Response
    {
        return $this->render('cart/index.html.twig', [
            'controller_name' => 'CartController',
        ]);
    }
    // Add more routes for cart functionality, such as adding items, removing items, updating quantities, etc.
    // For example:
     #[Route('/add/{id}', name: 'app_cart_add')]
     public function add(EquipmentRepository $equipment, SessionInterface $session): Response
     {
            dd($session);
        
     }
}
