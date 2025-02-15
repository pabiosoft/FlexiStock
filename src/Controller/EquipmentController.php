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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $limit = $request->query->getInt('limit', 5);
        
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
            // Check for existing equipment with same name or serial number
            $existingName = $entityManager->getRepository(Equipment::class)
                ->findOneBy(['name' => $equipment->getName()]);
            
            $existingSerial = $entityManager->getRepository(Equipment::class)
                ->findOneBy(['serialNumber' => $equipment->getSerialNumber()]);

            $errors = [];
            if ($existingName) {
                $errors[] = 'Un équipement avec ce nom existe déjà.';
            }
            if ($existingSerial) {
                $errors[] = 'Un équipement avec ce numéro de série existe déjà.';
            }

            // Validate required fields
            if (empty($equipment->getName())) {
                $errors[] = 'Le nom est requis.';
            }
            if (empty($equipment->getSerialNumber())) {
                $errors[] = 'Le numéro de série est requis.';
            }
            if (!$equipment->getCategory()) {
                $errors[] = 'La catégorie est requise.';
            }
            if ($equipment->getStockQuantity() < 0) {
                $errors[] = 'La quantité en stock ne peut pas être négative.';
            }
            if ($equipment->getMinThreshold() < 0) {
                $errors[] = 'Le seuil minimum ne peut pas être négatif.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('equipment/new.html.twig', [
                    'equipmentForm' => $form->createView(),
                ]);
            }

            try {
                $equipment->setAssignedUser($this->security->getUser());
                $equipment->setCreatedAt(new \DateTimeImmutable());
                $equipment->updateSlug();

                // Handle image uploads
                if ($form->has('images')) {
                    $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);
                }

                $entityManager->persist($equipment);
                $entityManager->flush();

                $this->addFlash('success', 'L\'équipement a bien été ajouté.');
                return $this->redirectToRoute('equipment_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout de l\'équipement: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('equipment/new.html.twig', [
            'equipmentForm' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Equipment $equipment, Request $request, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Store the original name and serial number before form handling
        $originalName = $equipment->getName();
        $originalSerialNumber = $equipment->getSerialNumber();
        
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('equipment_edit_' . $equipment->getId(), $token)) {
                throw $this->createAccessDeniedException('CSRF token is invalid.');
            }

            // Validate required fields
            $errors = [];
            if (empty($equipment->getName())) {
                $errors[] = 'Le nom est requis.';
            }
            if (empty($equipment->getSerialNumber())) {
                $errors[] = 'Le numéro de série est requis.';
            }
            if (!$equipment->getCategory()) {
                $errors[] = 'La catégorie est requise.';
            }
            if ($equipment->getStockQuantity() < 0) {
                $errors[] = 'La quantité en stock ne peut pas être négative.';
            }
            if ($equipment->getMinThreshold() < 0) {
                $errors[] = 'Le seuil minimum ne peut pas être négatif.';
            }
            
            // Check for duplicate name
            if ($originalName !== $equipment->getName() && !$this->isNameUnique($equipment, $entityManager)) {
                $errors[] = 'Un équipement avec ce nom existe déjà.';
            }
            
            // Check for duplicate serial number
            if ($originalSerialNumber !== $equipment->getSerialNumber() && !$this->isSerialNumberUnique($equipment, $entityManager)) {
                $errors[] = 'Un équipement avec ce numéro de série existe déjà.';
            }

            // If there are any errors, display them and return to the form
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('equipment/edit.html.twig', [
                    'equipmentForm' => $form->createView(),
                    'equipment' => $equipment,
                ]);
            }

            $equipment->setAssignedUser($this->security->getUser());

            // Only update slug if name has changed
            if ($originalName !== $equipment->getName()) {
                $equipment->updateSlug();
            }

            $this->handleExistingImages($equipment, $entityManager, $pictureService, $form->get('images')->getData());
            $this->handleImages($form->get('images')->getData(), $equipment, $entityManager, $pictureService);

            try {
                $entityManager->persist($equipment);
                $entityManager->flush();
                $this->addFlash('success', 'L\'équipement a bien été modifié.');
                return $this->redirectToRoute('equipment_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'équipement: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted()) {
            // If form is submitted but not valid, get the form errors
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
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
        $existingEquipment = $entityManager->getRepository(Equipment::class)
            ->createQueryBuilder('e')
            ->where('e.slug = :slug')
            ->setParameter('slug', $equipment->getSlug());

        // Only add ID check for existing equipment
        if ($equipment->getId() !== null) {
            $existingEquipment->andWhere('e.id != :id')
                ->setParameter('id', $equipment->getId());
        }

        return $existingEquipment->getQuery()->getOneOrNullResult() === null;
    }

    private function isNameUnique(Equipment $equipment, EntityManagerInterface $entityManager): bool
    {
        $existingEquipment = $entityManager->getRepository(Equipment::class)
            ->createQueryBuilder('e')
            ->where('e.name = :name')
            ->setParameter('name', $equipment->getName());

        // Only add ID check for existing equipment
        if ($equipment->getId() !== null) {
            $existingEquipment->andWhere('e.id != :id')
                ->setParameter('id', $equipment->getId());
        }

        return $existingEquipment->getQuery()->getOneOrNullResult() === null;
    }

    private function isSerialNumberUnique(Equipment $equipment, EntityManagerInterface $entityManager): bool
    {
        $existingEquipment = $entityManager->getRepository(Equipment::class)
            ->createQueryBuilder('e')
            ->where('e.serialNumber = :serialNumber')
            ->setParameter('serialNumber', $equipment->getSerialNumber());

        // Only add ID check for existing equipment
        if ($equipment->getId() !== null) {
            $existingEquipment->andWhere('e.id != :id')
                ->setParameter('id', $equipment->getId());
        }

        return $existingEquipment->getQuery()->getOneOrNullResult() === null;
    }

    #[Route('/template', name: 'equipment_import_template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = ['Nom', 'Description', 'Prix', 'Stock', 'Numéro de série', 'Marque', 'Modèle', 'Catégorie'];
        foreach (array_values($headers) as $i => $header) {
            $sheet->setCellValue(chr(65 + $i) . '1', $header);
        }
        
        // Add example data
        $exampleData = [
            ['Ordinateur portable', 'Dell XPS 13 - Processeur i7 16GB RAM', 1299.99, 5, 'XPS13-2024-001', 'Dell', 'XPS 13', 'Informatique'],
            ['Imprimante', 'HP LaserJet Pro - Impression recto-verso', 299.99, 3, 'HPLJ-2024-001', 'HP', 'LaserJet Pro', 'Périphériques']
        ];
        
        $row = 2;
        foreach ($exampleData as $data) {
            foreach ($data as $i => $value) {
                $sheet->setCellValue(chr(65 + $i) . $row, $value);
            }
            $row++;
        }
        
        // Style the header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A90E2'],
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        
        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create the response
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="equipment_import_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        
        return $response;
    }

    #[Route('/import-xlsx', name: 'equipment_import_xlsx', methods: ['POST'])]
    public function importXlsx(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var UploadedFile|null $xlsxFile */
        $xlsxFile = $request->files->get('xlsxFile');

        if (!$xlsxFile) {
            $this->addFlash('error', 'Aucun fichier n\'a été uploadé');
            return $this->redirectToRoute('equipment_index');
        }

        if ($xlsxFile->getClientOriginalExtension() !== 'xlsx') {
            $this->addFlash('error', 'Le fichier doit être au format XLSX');
            return $this->redirectToRoute('equipment_index');
        }

        try {
            $spreadsheet = IOFactory::load($xlsxFile->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            array_shift($rows); // Remove header row
            
            $errors = [];
            $count = 0;

            foreach ($rows as $rowIndex => $row) {
                try {
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Validate data
                    if (count($row) !== 8) { // Updated count to 8 columns
                        throw new \Exception("Nombre de colonnes incorrect (attendu: 8, reçu: " . count($row) . ")");
                    }

                    if (empty($row[0])) throw new \Exception("Le nom est requis");
                    if (empty($row[4])) throw new \Exception("Le numéro de série est requis");
                    if (empty($row[5])) throw new \Exception("La marque est requise");
                    if (empty($row[6])) throw new \Exception("Le modèle est requis");
                    if (empty($row[7])) throw new \Exception("La catégorie est requise");

                    // Check uniqueness
                    $repository = $entityManager->getRepository(Equipment::class);
                    
                    if ($repository->findOneBy(['name' => trim($row[0])])) {
                        throw new \Exception('Un équipement avec ce nom existe déjà');
                    }
                    
                    if ($repository->findOneBy(['serialNumber' => trim($row[4])])) {
                        throw new \Exception('Ce numéro de série existe déjà');
                    }
                    
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row[0])));
                    if ($repository->findOneBy(['slug' => $slug])) {
                        throw new \Exception('Un équipement avec ce slug existe déjà');
                    }

                    $equipment = new Equipment();
                    
                    // Set basic information
                    $equipment->setName(trim($row[0]));
                    $equipment->setDescription(trim($row[1] ?? ''));
                    $equipment->setPrice(abs((float)$row[2]));
                    $equipment->setStockQuantity(abs((int)$row[3]));
                    $equipment->setSerialNumber(trim($row[4]));
                    $equipment->setBrand(trim($row[5]));
                    $equipment->setModel(trim($row[6]));
                    $equipment->setSlug($slug);
                    
                    // Set default values
                    $equipment->setCreatedAt(new \DateTimeImmutable());
                    $equipment->setStatus('active');
                    $equipment->setMinThreshold(5);
                    $equipment->setLocation('Stock');
                    
                    // Set assigned user
                    $user = $this->security->getUser();
                    if (!$user) {
                        throw new \Exception("Utilisateur non authentifié");
                    }
                    $equipment->setAssignedUser($user);
                    
                    // Find or create category
                    $category = $entityManager->getRepository(Category::class)
                        ->findOneBy(['name' => trim($row[7])]);
                    
                    if (!$category) {
                        $category = new Category();
                        $category->setName(trim($row[7]));
                        $entityManager->persist($category);
                    }
                    
                    $equipment->setCategory($category);
                    $entityManager->persist($equipment);
                    $count++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Ligne " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            if (empty($errors)) {
                $entityManager->flush();
                $this->addFlash('success', $count . ' équipements ont été importés avec succès');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
        }

        return $this->redirectToRoute('equipment_index');
    }
}
