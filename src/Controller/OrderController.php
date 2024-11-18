<?php

namespace App\Controller;

use App\Form\OrderType;
use App\Entity\OrderItem;
use App\Entity\OrderRequest;
use App\Service\OrderService;
use App\Service\StockService;
use App\Service\InvoiceService;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private OrderService $orderService;
    private InvoiceService $invoiceService;

    public function __construct(EntityManagerInterface $entityManager, OrderService $orderService, InvoiceService $invoiceService)
    {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
    }

    #[Route('/order/create', name: 'order_create')]
    public function create(
        Request $request,
        StockService $stockService,
        EquipmentRepository $equipmentRepository
    ): Response {
        $orderRequest = new OrderRequest();
        $orderItem = new OrderItem();
        $orderItem->setQuantity(1); // Example default quantity
        $orderRequest->addItem($orderItem); // Add the OrderItem to the OrderRequest

        $equipmentChoices = $equipmentRepository->findBy([], ['name' => 'ASC']); // Fetch equipment choices();
        

        // Create the form for the OrderRequest entity
        $form = $this->createForm(OrderType::class, $orderRequest, [
            'equipment_choices' => $equipmentChoices, // Pass the equipment choices to the form
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Check stock availability for all items
                $insufficientItems = [];
                foreach ($orderRequest->getItems() as $item) {
                    if (!$item || !$item->getEquipment() || !$stockService->checkStock($item)) {
                        $insufficientItems[] = sprintf(
                            '%s (Requested: %d, Available: %d)',
                            $item->getEquipment()->getName(),
                            $item->getQuantity(),
                            $item->getEquipment()->getStockQuantity()
                        );
                    }
                }

                // If there are insufficient items, show an error
                if (!empty($insufficientItems)) {
                    $this->addFlash('error', 'Insufficient stock for: ' . implode(', ', $insufficientItems));
                    return $this->redirectToRoute('order_create');
                }

                // Set the order status as pending
                $orderRequest->setStatus('pending');
                foreach ($orderRequest->getItems() as $item) {
                    if (!$item) {
                        throw new \RuntimeException('Order item is null');
                    }
                    $stockService->reserveStock($item); // Reserve stock for each item
                }

                // Persist the order and flush to database
                $this->entityManager->persist($orderRequest);
                $this->entityManager->flush();

                // Generate the invoice
                // $this->invoiceService->generateInvoice($orderRequest);

                $this->addFlash('success', 'Order placed successfully!');
                return $this->redirectToRoute('order_show', ['id' => $orderRequest->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An unexpected error occurred. Please contact support: ' . $e->getMessage());
            }
        }

        // Render the order creation form
        return $this->render('order/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/order/{id}', name: 'order_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('The order does not exist');
        }

        return $this->render('order/show.html.twig', [
            'order' => $orderRequest,
        ]);
    }

    #[Route("/order/{id}/validate", name: "order_validate")]
    public function validateOrder(int $id): Response
    {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('Order not found.');
        }

        // Mark the order as validated and paid
        $orderRequest->setStatus('validated');
        $orderRequest->setValidatedAt(new \DateTime());

        $this->entityManager->flush(); // Persist changes to the database

        $this->addFlash('success', 'Order validated and marked as paid.');
        return $this->redirectToRoute('order_list', ['id' => $orderRequest->getId()]);
    }

    #[Route("/order/list", name: "order_list")]
    public function list(): Response
    {
        $orderRequests = $this->entityManager->getRepository(OrderRequest::class)->findAll();
        return $this->render('order/index.html.twig', [
            'orders' => $orderRequests,
        ]);
    }

    #[Route('/order/{id}/invoice', name: 'order_invoice')]
    public function generateInvoice(int $id): Response
    {
        $orderRequest = $this->entityManager->getRepository(OrderRequest::class)->find($id);

        if (!$orderRequest) {
            throw $this->createNotFoundException('Order not found.');
        }

        try {
            $pdfPath = $this->invoiceService->generateInvoice($orderRequest);
            $this->addFlash('success', 'Invoice generated successfully.');

            // Offer the file as a download
            return $this->file($pdfPath, 'invoice-' . $orderRequest->getId() . '.pdf');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to generate invoice: ' . $e->getMessage());
            return $this->redirectToRoute('order_show', ['id' => $id]);
        }
    }
    #[Route('/order/{id}/view-invoice', name: 'order_view_invoice', requirements: ['id' => '\d+'])]
    public function viewInvoice(OrderRequest $orderRequest): Response
    {
        // Path to the invoice PDF file
        $invoiceFile = $this->invoiceService->generateInvoice($orderRequest);

        // Check if the file exists
        if (!file_exists($invoiceFile)) {
            throw $this->createNotFoundException('Invoice not found.');
        }

        // Serve the PDF file as a response
        return $this->file($invoiceFile, 'invoice-' . $orderRequest->getId() . '.pdf', ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
