<?php

namespace App\Service;

use App\Entity\OrderRequest;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class OrderNotificationService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private UrlGeneratorInterface $urlGenerator;
    private string $adminEmail;
    private string $fromEmail;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        string $adminEmail,
        string $fromEmail
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->adminEmail = $adminEmail;
        $this->fromEmail = $fromEmail;
    }

    public function sendOrderConfirmation(OrderRequest $order): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($order->getCustomer()->getEmail())
            ->subject('Order Confirmation - #' . $order->getId())
            ->html(
                $this->twig->render('emails/order_confirmation.html.twig', [
                    'order' => $order,
                    'viewUrl' => $this->urlGenerator->generate('order_show', [
                        'id' => $order->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
        }
    }

    public function sendStatusUpdate(OrderRequest $order): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($order->getCustomer()->getEmail())
            ->subject('Order Status Update - #' . $order->getId())
            ->html(
                $this->twig->render('emails/order_status_update.html.twig', [
                    'order' => $order,
                    'viewUrl' => $this->urlGenerator->generate('order_show', [
                        'id' => $order->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ])
            );

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
        }
    }

    public function notifyLowStock(OrderRequest $order): void
    {
        $lowStockItems = [];
        foreach ($order->getItems() as $item) {
            $equipment = $item->getEquipment();
            if ($equipment->getStockQuantity() <= $equipment->getMinThreshold()) {
                $lowStockItems[] = [
                    'name' => $equipment->getName(),
                    'current' => $equipment->getStockQuantity(),
                    'threshold' => $equipment->getMinThreshold()
                ];
            }
        }

        if (!empty($lowStockItems)) {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($this->adminEmail)
                ->subject('Low Stock Alert - Order #' . $order->getId())
                ->html(
                    $this->twig->render('emails/low_stock_alert.html.twig', [
                        'items' => $lowStockItems
                    ])
                );

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log the error or handle it as needed
            }
        }
    }
}