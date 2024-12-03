<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Category;
use App\Entity\Equipment;
use App\Form\EquipmentType;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Entity\Image;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'equipment_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        // Get category value and convert to int only if not empty
        $categoryValue = $request->query->get('category');
        $category = !empty($categoryValue) ? (int)$categoryValue : null;
        
        $criteria = [
            'name' => $request->query->get('name'),
            'category' => $category,
            'status' => $request->query->get('status'),
            'lowStock' => $request->query->getBoolean('lowStock')
        ];

        $paginatedData = $entityManager->getRepository(Equipment::class)->getPaginatedEquipment($page, $limit, $criteria);
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('equipment/index.html.twig', [
            'equipments' => $paginatedData['items'],
            'categories' => $categories,
            'pagination' => [
                'currentPage' => $paginatedData['currentPage'],
                'pageCount' => $paginatedData['pageCount'],
                'totalItems' => $paginatedData['totalItems'],
                'itemsPerPage' => $paginatedData['itemsPerPage']
            ]
        ]);
    }

    #[Route('/new', name: 'equipment_new', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('new_equipment', $token)) {
                throw $this->createAccessDeniedException('CSRF token is invalid.');
            }
            // Automatically assign the current user
            $equipment->setAssignedUser($this->security->getUser());

            $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);
            $equipment->setCreatedAt(new \DateTimeImmutable());

            // On arrondit le prix 
            // $prix = $equipment->getPrice() * $equipment->getQuantity();
            // $equipment->setPrice($prix);

            if ($this->isSlugUnique($equipment, $entityManager)) {
                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', 'L\'équipement a bien été ajouté.');
                return $this->redirectToRoute('equipment_index');
            } else {
                $this->addFlash('error', 'Cet équipement existe déjà.');
            }
        }
        // $this->addFlash('error', 'Veuillez remplir tous les champs.');

        return $this->render('equipment/new.html.twig', [
            'equipmentForm' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Equipment $equipment, Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('equipment_edit_' . $equipment->getId(), $token)) {
                throw $this->createAccessDeniedException('CSRF token is invalid.');
            }
            $equipment->setAssignedUser($this->security->getUser());

            $this->handleExistingImages($equipment, $entityManager, $pictureService, $form->get('images')->getData());
            $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);


            if ($this->isSlugUnique($equipment, $entityManager)) {
                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', 'L\'équipement a bien été modifié.');
                return $this->redirectToRoute('equipment_index');
            } else {
                $this->addFlash('error', 'Cet équipement existe déjà.');
            }
        }

        return $this->render('equipment/edit.html.twig', [
            'equipmentForm' => $form->createView(),
            'equipment' => $equipment,
        ]);
    }

    #[Route('/{id}/delete', name: 'equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete-equipment-' . $equipment->getId(), $request->request->get('_token'))) {
            try {
                // Check if equipment has active reservations
                $reservations = $equipment->getReservations();
                if (!$reservations->isEmpty()) {
                    $this->addFlash('error', 'Impossible de supprimer cet équipement car il a des réservations associées.');
                    return $this->redirectToRoute('equipment_edit', ['id' => $equipment->getId()]);
                }

                // Delete associated images first
                foreach ($equipment->getImages() as $image) {
                    // Use PictureService to delete images
                    $pictureService->delete($image->getName(), 'equipments', 300, 300);
                    $entityManager->remove($image);
                }

                // Then delete the equipment
                $entityManager->remove($equipment);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'équipement a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'équipement: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('equipment_index');
    }

    #[Route('/show/{id}', name: 'equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
        ]);
    }
    // filtrage AND SEARCH

    #[Route('/search', name: 'equipment_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        $criteria = [
            'name' => $request->query->get('name'),
            'brand' => $request->query->get('brand'),
            'status' => $request->query->get('status'),
            'category' => $request->query->get('category'),
        ];

        $equipments = $entityManager->getRepository(Equipment::class)->searchEquipments($criteria);

        return $this->render('equipment/index.html.twig', [
            'equipments' => $equipments,
            'categories' => $categories,
        ]);
    }

    #[Route('/export', name: 'equipment_export', methods: ['GET'])]
    public function export(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $equipments = $entityManager->getRepository(Equipment::class)->findAll();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nom');
        $sheet->setCellValue('C1', 'Catégorie');
        $sheet->setCellValue('D1', 'Marque');
        $sheet->setCellValue('E1', 'Modèle');
        $sheet->setCellValue('F1', 'Prix');
        $sheet->setCellValue('G1', 'Quantité');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'Date de création');
        
        // Style the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A90E2'],
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Add data
        $row = 2;
        foreach ($equipments as $equipment) {
            $sheet->setCellValue('A' . $row, $equipment->getId());
            $sheet->setCellValue('B' . $row, $equipment->getName());
            $sheet->setCellValue('C' . $row, $equipment->getCategory() ? $equipment->getCategory()->getName() : '');
            $sheet->setCellValue('D' . $row, $equipment->getBrand());
            $sheet->setCellValue('E' . $row, $equipment->getModel());
            $sheet->setCellValue('F' . $row, $equipment->getPrice());
            $sheet->setCellValue('G' . $row, $equipment->getStockQuantity());
            $sheet->setCellValue('H' . $row, $equipment->getStatus());
            $sheet->setCellValue('I' . $row, $equipment->getCreatedAt() ? $equipment->getCreatedAt()->format('Y-m-d H:i:s') : '');
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create the response
        $response = new StreamedResponse(function() use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
        
        // Set response headers
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="equipments_export_' . date('Y-m-d_His') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        
        return $response;
    }

    #[Route('/{id}/image/{image_id}/delete', name: 'equipment_delete_image', methods: ['POST'])]
    public function deleteImage(
        Equipment $equipment,
        #[ParamConverter('image', options: ['mapping' => ['image_id' => 'id']])] Image $image,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $request->request->get('_token'))) {
            // Remove the image file from storage
            $imagePath = $this->getParameter('equipment_images_directory') . '/' . $image->getName();
            $miniImagePath = $this->getParameter('equipment_images_directory') . '/mini/300x300-' . $image->getName();
            
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            if (file_exists($miniImagePath)) {
                unlink($miniImagePath);
            }

            // Remove the image from the equipment and delete it from the database
            $equipment->removeImage($image);
            $entityManager->remove($image);
            $entityManager->flush();

            $this->addFlash('success', 'L\'image a été supprimée avec succès.');
        }

        return $this->redirectToRoute('equipment_edit', ['id' => $equipment->getId()]);
    }

    // GESTION DES IMAGES
    private function handleImages(?array $images, Equipment $equipment, EntityManagerInterface $entityManager, PictureService $pictureService): void
    {
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
