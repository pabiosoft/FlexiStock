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

#[Route('/supplier', name: 'supplier_')]
class SupplierController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, SupplierRepository $supplierRepository): Response
    {
        $searchTerm = $request->query->get('q');
        
        if ($searchTerm) {
            $suppliers = $supplierRepository->findBySearch($searchTerm);
        } else {
            $suppliers = $supplierRepository->findAll();
        }

        return $this->render('supplier/index.html.twig', [
            'suppliers' => $suppliers,
            'searchTerm' => $searchTerm
        ]);
    }
    

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $supplier = new Supplier();
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($supplier);
            $entityManager->flush();

            $this->addFlash('success', 'Le fournisseur a bien été ajouté');

            return $this->redirectToRoute('supplier_index');
        }

        return $this->render('supplier/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le fournisseur a bien été modifié');

            return $this->redirectToRoute('supplier_index');
        }

        return $this->render('supplier/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $supplier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($supplier);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Le fournisseur a bien été supprimé');

        return $this->redirectToRoute('supplier_index');
    }

    #[Route('/{id}', name: 'show')]
    public function show(Supplier $supplier): Response
    {
        return $this->render('supplier/show.html.twig', [
            'suppliers' => $supplier,
        ]);
    }
}
