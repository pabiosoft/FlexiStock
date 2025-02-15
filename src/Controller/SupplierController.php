<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/supplier')]
class SupplierController extends AbstractController
{
    #[Route('/', name: 'supplier_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 6);
        
        $queryBuilder = $entityManager->getRepository(Supplier::class)
            ->createQueryBuilder('s');

        // Add search filter
        if ($search = $request->query->get('search')) {
            $queryBuilder
                ->where('s.name LIKE :search')
                ->orWhere('s.email LIKE :search')
                ->orWhere('s.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Add status filter
        if ($status = $request->query->get('status')) {
            $queryBuilder
                ->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        // Get total items before pagination
        $totalItems = count($queryBuilder->getQuery()->getResult());

        // Add pagination
        $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('s.name', 'ASC');

        $suppliers = $queryBuilder->getQuery()->getResult();
        $pageCount = ceil($totalItems / $limit);

        return $this->render('supplier/index.html.twig', [
            'suppliers' => $suppliers,
            'pagination' => [
                'currentPage' => $page,
                'pageCount' => $pageCount,
                'totalItems' => $totalItems,
                'itemsPerPage' => $limit
            ]
        ]);
    }
    

    #[Route('/new', name: 'supplier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $supplier = new Supplier();
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($supplier);
            $entityManager->flush();

            $this->addFlash('success', 'Le fournisseur a bien été ajouté');
            return $this->redirectToRoute('supplier_index');
        }

        return $this->render('supplier/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le fournisseur a bien été modifié');
            return $this->redirectToRoute('supplier_index');
        }

        return $this->render('supplier/edit.html.twig', [
            'form' => $form->createView(),
            'supplier' => $supplier,
        ]);
    }

    #[Route('/{id}', name: 'supplier_show', methods: ['GET'])]
    public function show(Supplier $supplier): Response
    {
        return $this->render('supplier/show.html.twig', [
            'supplier' => $supplier,
        ]);
    }

    #[Route('/{id}/delete', name: 'supplier_delete', methods: ['POST'])]
    public function delete(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$supplier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($supplier);
            $entityManager->flush();
            $this->addFlash('success', 'Le fournisseur a bien été supprimé');
        }

        return $this->redirectToRoute('supplier_index');
    }
}
