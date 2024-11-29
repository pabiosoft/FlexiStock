<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Equipment;
use App\Enum\AlertLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StockAlertService
{
    private $entityManager;
    private $mailer;
    private $params;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->params = $params;
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
            ->from($this->params->get('app.mail_from'))
            ->to($this->params->get('app.maintenance_notification_email'))
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

        $this->sendExpirationEmail($equipment);
    }

    private function sendExpirationEmail(Equipment $equipment): void
    {
        $email = (new Email())
            ->from($this->params->get('app.mail_from'))
            ->to($this->params->get('app.maintenance_notification_email'))
            ->subject('Warranty Expiration Alert - ' . $equipment->getName())
            ->html(sprintf(
                '<p>Warranty expiration alert for %s</p>
                <ul>
                    <li>Equipment Name: %s</li>
                    <li>Serial Number: %s</li>
                    <li>Warranty Expiration Date: %s</li>
                </ul>
                <p>Please review and take necessary action.</p>',
                $equipment->getName(),
                $equipment->getName(),
                $equipment->getSerialNumber(),
                $equipment->getWarrantyDate()->format('Y-m-d')
            ));

        $this->mailer->send($email);
    }
}
