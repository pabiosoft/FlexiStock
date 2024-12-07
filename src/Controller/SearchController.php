<?php

namespace App\Controller;

use App\Repository\EquipmentRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/search')]
class SearchController extends AbstractController
{
    private $equipmentRepository;
    private $categoryRepository;
    private $userRepository;

    public function __construct(
        EquipmentRepository $equipmentRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository
    ) {
        $this->equipmentRepository = $equipmentRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = [];

        if (strlen($query) >= 2) {
            // Search in equipment
            $equipment = $this->equipmentRepository->findFilteredEquipment($query);
            foreach ($equipment as $item) {
                $suggestions[] = [
                    'type' => 'equipment',
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'url' => $this->generateUrl('equipment_show', ['id' => $item->getId()]),
                    'category' => $item->getCategory() ? $item->getCategory()->getName() : null,
                    'status' => $item->getStatus()
                ];
            }

            // Search in categories
            $categories = $this->categoryRepository->findBySearch($query)['items'];
            foreach ($categories as $category) {
                $suggestions[] = [
                    'type' => 'category',
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'url' => $this->generateUrl('category_equipments', ['slug' => $category->getSlug()]),
                    'parent' => $category->getParent() ? $category->getParent()->getName() : null,
                    'equipmentCount' => $category->getEquipmentItems()->count()
                ];
            }
        }

        return new JsonResponse($suggestions);
    }

    #[Route('/results', name: 'search_results', methods: ['GET'])]
    public function results(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all');

        $results = [
            'equipment' => [],
            'categories' => [],
        ];

        if ($type === 'all' || $type === 'equipment') {
            $results['equipment'] = $this->equipmentRepository->findFilteredEquipment($query);
        }

        if ($type === 'all' || $type === 'category') {
            $results['categories'] = $this->categoryRepository->findBySearch($query)['items'];
        }

        return $this->render('search/results.html.twig', [
            'query' => $query,
            'type' => $type,
            'results' => $results,
        ]);
    }
}
