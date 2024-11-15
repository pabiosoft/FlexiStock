<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/category')]

class CategoryController extends AbstractController
{
    
    #[Route('/', name: 'category_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->updateSlug();

            // Check if the slug is already in use
            $existingCategory = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $category->getSlug()]);
            if ($existingCategory) {
                $this->addFlash('error', 'A category with this slug already exists.');
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

    // #[Route('/{slug}', name: 'category_show', methods: ['GET'])]
    // public function show(string $slug, EntityManagerInterface $entityManager): Response
    // {
    //     $category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

    //     if (!$category) {
    //         throw $this->createNotFoundException('The category does not exist');
    //     }

    //     return $this->render('category/show.html.twig', [
    //         'category' => $category,
    //     ]);
    // }

    #[Route('/{slug}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $slug, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('The category does not exist');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->updateSlug();

            // Check if the new slug is already in use by another category
            $existingCategory = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $category->getSlug()]);
            if ($existingCategory && $existingCategory->getId() !== $category->getId()) {
                $this->addFlash('error', 'A category with this slug already exists.');
                return $this->redirectToRoute('category_edit', ['slug' => $category->getSlug()]);
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

    #[Route('/{slug}', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, string $slug, EntityManagerInterface $entityManager): Response
    {
        $category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('The category does not exist');
        }

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('category_index');
    }
    // afficher les équipements d'une catégorie :
    
    #[Route('/{slug}/equipments', name: 'category_equipments', methods: ['GET'])]
    public function showEquipments($slug, CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        // on va chercher la liste des equipments de la catégorie
        $equipments = $category->getEquipmentItems();


        return $this->render('category/show.html.twig', compact('category', 'equipments'));
    }
}
