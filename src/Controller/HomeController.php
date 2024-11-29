<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use App\Entity\OrderRequest;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CategoryRepository $categoryRepository, EquipmentRepository $equipmentRepository): Response
    {
        $categories = $categoryRepository->findBy([], ['categoryOrder' => 'ASC']);
        $equipments = $equipmentRepository->findAll();

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'equipments' => $equipments,
        ]);
    }

    #[Route('/order/{id<\d+>}', name: 'order_equipment', methods: ['POST'])]
    public function orderEquipment(
        int $id, 
        Request $request,
        EquipmentRepository $equipmentRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $equipment = $equipmentRepository->find($id);
        $quantity = (int) $request->request->get('quantity', 1);

        if (!$equipment) {
            $this->addFlash('error', 'Equipment not found.');
            return $this->redirectToRoute('app_home');
        }

        if ($quantity <= 0) {
            $this->addFlash('error', 'Invalid quantity.');
            return $this->redirectToRoute('app_home');
        }

        if ($equipment->getStockQuantity() < $quantity) {
            $this->addFlash('error', 'Not enough stock available.');
            return $this->redirectToRoute('app_home');
        }

        $orderRequest = new OrderRequest();
        $orderRequest->setCustomer($this->getUser());
        $orderRequest->setOrderDate(new \DateTime());
        $orderRequest->setStatus('pending');
        $orderRequest->setPaymentStatus('pending');

        // Create order item
        $orderItem = new OrderItem();
        $orderItem->setEquipment($equipment);
        $orderItem->setQuantity($quantity);
        $orderItem->setUnitPrice($equipment->getPrice());
        $orderItem->setOrderRequest($orderRequest);

        // Update equipment stock
        $equipment->setStockQuantity($equipment->getStockQuantity() - $quantity);

        $entityManager->persist($orderRequest);
        $entityManager->persist($orderItem);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Successfully ordered %d unit(s) of %s.', $quantity, $equipment->getName()));

        return $this->redirectToRoute('app_home');
    }
}
