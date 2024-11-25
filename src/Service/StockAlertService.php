<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Equipment;
use App\Enum\AlertLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class StockAlertService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private string $adminEmail;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        string $adminEmail
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function checkLowStockLevels(): void
    {
        $equipmentRepository = $this->entityManager->getRepository(Equipment::class);
        $lowStockItems = $equipmentRepository->findLowStockItems();

        foreach ($lowStockItems as $equipment) {
            $this->createLowStockAlert($equipment);
        }
    }

    private function createLowStockAlert(Equipment $equipment): void
    {
        $alert = new Alert();
        $alert->setLevel(AlertLevel::WARNING);
        $alert->setMessage(sprintf(
            'Low stock alert for %s: %d items remaining (Minimum threshold: %d)',
            $equipment->getName(),
            $equipment->getStockQuantity(),
            $equipment->getMinThreshold()
        ));
        $alert->setCreatedAt(new \DateTime());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        $this->sendLowStockEmail($equipment);
    }

    private function sendLowStockEmail(Equipment $equipment): void
    {
        $email = (new Email())
            ->from('reply@mailtrap.club')
            ->to($this->adminEmail)
            ->subject('Low Stock Alert - ' . $equipment->getName())
            ->html(sprintf(
                '<p>Low stock alert for %s</p>
                <ul>
                    <li>Current stock: %d</li>
                    <li>Minimum threshold: %d</li>
                    <li>Serial Number: %s</li>
                </ul>
                <p>Please review and reorder if necessary.</p>',
                $equipment->getName(),
                $equipment->getStockQuantity(),
                $equipment->getMinThreshold(),
                $equipment->getSerialNumber()
            ));

        $this->mailer->send($email);
    }

    public function checkExpiringItems(): void
    {
        $equipmentRepository = $this->entityManager->getRepository(Equipment::class);
        $expiringItems = $equipmentRepository->findExpiringItems();

        foreach ($expiringItems as $equipment) {
            $this->createExpirationAlert($equipment);
        }
    }

    private function createExpirationAlert(Equipment $equipment): void
    {
        $alert = new Alert();
        $alert->setLevel(AlertLevel::WARNING);
        $alert->setMessage(sprintf(
            'Warranty expiring soon for %s (Serial: %s). Expiration date: %s',
            $equipment->getName(),
            $equipment->getSerialNumber(),
            $equipment->getWarrantyDate()->format('Y-m-d')
        ));
        $alert->setCreatedAt(new \DateTime());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();
    }
}
