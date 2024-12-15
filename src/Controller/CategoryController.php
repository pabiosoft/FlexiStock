<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/category')]
class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;

    public function __construct(EntityManagerInterface $entityManager, CategoryRepository $categoryRepository)
    {
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
    }

    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'name');
        $direction = $request->query->get('direction', 'ASC');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 10);

        $result = $categoryRepository->findBySearch($search, $sortBy, $direction, $page, $limit);

        return $this->render('category/index.html.twig', [
            'categories' => $result['items'],
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
            'search' => $search,
            'pagination' => [
                'currentPage' => $result['currentPage'],
                'totalPages' => $result['totalPages'],
                'itemsPerPage' => $result['itemsPerPage'],
                'totalItems' => $result['totalItems']
            ]
        ]);
    }

    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'categories' => $categoryRepository->findAll(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->updateSlug();

            // Validate no cyclic relationship
            if ($this->isCyclicRelationship($category)) {
                $this->addFlash('error', 'Invalid hierarchy: a category cannot be its own parent or create a cyclic relationship.');
                return $this->redirectToRoute('category_new');
            }

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category created successfully.');
            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'categories' => $categoryRepository->findAll(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->updateSlug();

            // Validate no cyclic relationship
            if ($this->isCyclicRelationship($category)) {
                $this->addFlash('error', 'Invalid hierarchy: a category cannot be its own parent or create a cyclic relationship.');
                return $this->redirectToRoute('category_edit', ['id' => $category->getId()]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Category updated successfully.');
            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Category deleted successfully.');
        }

        return $this->redirectToRoute('category_index');
    }

    #[Route('/{slug}/equipments', name: 'category_equipments', methods: ['GET'])]
    public function showEquipments(string $slug): Response
    {
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('La catégorie n\'existe pas.');
        }

        $equipments = $category->getEquipmentItems();

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'equipments' => $equipments,
        ]);
    }

    private function isCyclicRelationship(Category $category): bool
    {
        $parent = $category->getParent();
        while ($parent !== null) {
            if ($parent === $category) {
                return true; // Cyclic relationship found
            }
            $parent = $parent->getParent();
        }
        return false;
    }
    // Other methods for the hierarchy management
    // ...
}
