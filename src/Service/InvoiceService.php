<?php

namespace App\Service;

use App\Entity\OrderRequest;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class InvoiceService
{
    private string $invoiceDirectory;
    private Environment $twig;

    public function __construct(string $invoiceDirectory, Environment $twig)
    {
        $this->invoiceDirectory = $invoiceDirectory;
        $this->twig = $twig;
    }

    public function generateInvoice(OrderRequest $orderRequest): string
    {
        // Ensure the invoice directory exists
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->invoiceDirectory)) {
            $filesystem->mkdir($this->invoiceDirectory);
        }

        // Configure Dompdf options
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        // Use Twig to generate the invoice HTML
        $html = $this->twig->render('order/invoice/invoice.html.twig', [
            'order' => $orderRequest,
        ]);

        // Load HTML into Dompdf
        $dompdf->loadHtml($html);

        // Set the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the PDF
        $dompdf->render();

        // Save the PDF to a file
        $filename = $this->invoiceDirectory . '/invoice-' . $orderRequest->getId() . '.pdf';
        file_put_contents($filename, $dompdf->output());

        return $filename;
    }
}
