<?php

namespace App\Controller;

use App\Entity\Movement;
use App\Form\MovementType;
use App\Service\MovementService;
use Symfony\Component\Form\FormError;
use App\Repository\EquipmentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MovementController extends AbstractController
{
    private $movementService;

    public function __construct(MovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    #[Route('/movement/new', name: 'movement_new')]
    public function new(Request $request): Response
    {
        $movement = new Movement();
        $form = $this->createForm(MovementType::class, $movement);
    
        // Handle the form submission
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Check if quantity is valid before processing
                if ($movement->getType() === 'OUT' && $movement->getQuantity() <= 0) {
                    $this->addFlash('error', 'Quantity must be greater than 0 for an OUT movement.');
                } else {
                    // Create the movement using the service
                    $this->movementService->createMovement($movement);
                    $this->addFlash('success', 'Movement created successfully!');
                    return $this->redirectToRoute('movement_index');
                }
            } catch (\Exception $e) {
                $form->get('type')->addError(new FormError('An error occurred: ' . $e->getMessage()));
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            }
        }
    
        return $this->render('movement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    
    #[Route('/movement/{id}', name: 'movement_show')]
    public function show(Movement $movement): Response
    {
        return $this->render('movement/show.html.twig', [
            'movement' => $movement,
        ]);
    }

    #[Route('/movement', name: 'movement_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        // Get paginated movements using the service
        $movements = $this->movementService->getPaginatedMovements($page, $limit);

        $totalMovements = $this->movementService->getTotalMovements();
        $totalPages = ceil($totalMovements / $limit);

        $pagination = [
            'currentPage' => $page,
            'pageCount' => $totalPages,
            'totalItems' => $totalMovements,
            'itemsPerPage' => $limit,
        ];

        return $this->render('movement/index.html.twig', [
            'movements' => $movements,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/movements/{id}/edit', name: 'movement_edit')]
    public function edit(Request $request, Movement $movement): Response
    {
        if (!$movement) {
            throw $this->createNotFoundException('Movement not found.');
        }

        $form = $this->createForm(MovementType::class, $movement);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->movementService->updateMovement($movement);
                $this->addFlash('success', 'Movement updated successfully!');
                return $this->redirectToRoute('movement_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('movement/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/equipments/by-category/{categoryId}', name: 'get_equipments_by_category')]
    public function getEquipmentsByCategory(EquipmentRepository $equipmentRepository, int $categoryId): JsonResponse
    {
        $equipments = $equipmentRepository->findBy(['category' => $categoryId]);

        $data = [];
        foreach ($equipments as $equipment) {
            $data[] = [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/movement/{id}/delete', name: 'movement_delete')]
    public function delete(Movement $movement): RedirectResponse
    {
        try {
            // Delete the movement using the service
            $this->movementService->deleteMovement($movement);
            $this->addFlash('success', 'Movement deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
        }

        return $this->redirectToRoute('movement_index');
    }
}
