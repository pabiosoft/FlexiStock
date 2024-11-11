<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Equipment;
use App\Form\EquipmentType;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    #[Route('/', name: 'equipment_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $equipments = $entityManager->getRepository(Equipment::class)->findAll();
        return $this->render('equipment/index.html.twig', [
            'equipments' => $equipments,
        ]);
    }

    #[Route('/new', name: 'equipment_new', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('new_equipment', $token)) {
                throw $this->createAccessDeniedException('CSRF token is invalid.');
            }

            $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);
            $equipment->setCreatedAt(new \DateTimeImmutable());

            if ($this->isSlugUnique($equipment, $entityManager)) {
                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', 'L\'équipement a bien été ajouté.');
                return $this->redirectToRoute('equipment_index');
            } else {
                $this->addFlash('error', 'Cet équipement existe déjà.');
                return $this->redirectToRoute('equipment_index');
            }
        }

        return $this->render('equipment/new.html.twig', [
            'equipmentForm' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Equipment $equipment, Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit_equipment_' . $equipment->getId(), $token)) {
                throw $this->createAccessDeniedException('CSRF token is invalid.');
            }

            $this->handleExistingImages($equipment, $entityManager, $pictureService, $form->get('images')->getData());
            $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);

            if ($this->isSlugUnique($equipment, $entityManager)) {
                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', 'L\'équipement a bien été modifié.');
                return $this->redirectToRoute('equipment_index');
            } else {
                $this->addFlash('error', 'Cet équipement existe déjà.');
                return $this->redirectToRoute('equipment_index');
            }
        }

        return $this->render('equipment/edit.html.twig', [
            'equipmentForm' => $form->createView(),
            'equipment' => $equipment,
        ]);
    }
    //show equipment
    #[Route('/show/{id}', name: 'equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment): Response
    {
        
        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
        ]);
    }
    #[Route('/delete/{id}', name: 'equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_equipment_' . $equipment->getId(), $token)) {
            throw $this->createAccessDeniedException('CSRF token is invalid.');
        }

        foreach ($equipment->getImages() as $image) {
            $pictureService->delete($image->getName(), 'equipments', 300, 300);
        }

        $entityManager->remove($equipment);
        $entityManager->flush();

        $this->addFlash('success', 'L\'équipement et son image ont bien été supprimés.');

        return $this->redirectToRoute('equipment_index');
    }

    private function handleImages(?array $images, Equipment $equipment, EntityManagerInterface $entityManager, PictureService $pictureService): void
    {
        if ($images) {
            foreach ($images as $image) {
                try {
                    $fileName = $pictureService->add($image, 'equipments', 300, 300);
                    if ($fileName) {
                        $img = new Images();
                        $img->setName($fileName);
                        $equipment->addImage($img);
                        $entityManager->persist($img);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'ajout de l\'image : ' . $e->getMessage());
                }
            }
        } else {
            $this->addFlash('info', 'Aucune image ajoutée.');
        }
    }

    private function handleExistingImages(Equipment $equipment, EntityManagerInterface $entityManager, PictureService $pictureService, ?array $newImages = null): void
    {
        if ($newImages) {
            foreach ($equipment->getImages() as $existingImage) {
                $pictureService->delete($existingImage->getName(), 'equipments', 300, 300);
                $entityManager->remove($existingImage);
            }
            $entityManager->flush();
        }
    }

    private function isSlugUnique(Equipment $equipment, EntityManagerInterface $entityManager): bool
    {
        $existingEquipment = $entityManager->getRepository(Equipment::class)->findOneBy(['slug' => $equipment->getSlug()]);
        return !$existingEquipment || $existingEquipment->getId() === $equipment->getId();
    }
}
