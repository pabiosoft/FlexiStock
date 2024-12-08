<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Equipment;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\AlertCategory;
use App\Enum\AlertPriority;
use App\Enum\AlertLevel;

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private AlertRepository $alertRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AlertRepository $alertRepository
    ) {
        $this->entityManager = $entityManager;
        $this->alertRepository = $alertRepository;
    }

    public function createAlert(
        string $message,
        AlertLevel $level = AlertLevel::INFO,
        ?Equipment $equipment = null,
        ?AlertCategory $category = null,
        AlertPriority $priority = AlertPriority::MEDIUM,
        bool $persistent = false
    ): Alert {
        $alert = new Alert();
        $alert->setMessage($message)
            ->setLevel($level->value)
            ->setPriority($priority->value)
            ->setPersistent($persistent)
            ->setCreatedAt(new \DateTime());

        if ($equipment) {
            $alert->setEquipment($equipment);
        }

        if ($category) {
            $alert->setCategory($category->value);
        }

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    public function getUnreadAlerts(
        int $page = 1,
        int $limit = 10,
        ?AlertLevel $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        return $this->alertRepository->findUnreadAlerts($page, $limit, $level ? $level->value : null, $fromDate, $toDate);
    }

    public function markAsRead(Alert $alert): void
    {
        if (!$alert->isPersistent()) {
            $this->entityManager->remove($alert);
        } else {
            $alert->setReadAt(new \DateTime());
        }
        $this->entityManager->flush();
    }

    public function bulkMarkAsRead(array $ids): int
    {
        $alerts = $this->alertRepository->findBy(['id' => $ids]);
        $markedCount = 0;

        foreach ($alerts as $alert) {
            if (!$alert->isPersistent()) {
                $this->entityManager->remove($alert);
            } else {
                $alert->setReadAt(new \DateTime());
            }
            $markedCount++;
        }

        $this->entityManager->flush();
        return $markedCount;
    }

    public function getUnreadCount(?AlertLevel $level = null): int
    {
        return $this->alertRepository->countUnreadAlerts($level ? $level->value : null);
    }

    public function clearAllNonPersistent(): void
    {
        $alerts = $this->alertRepository->findBy(['persistent' => false]);
        foreach ($alerts as $alert) {
            $this->entityManager->remove($alert);
        }
        $this->entityManager->flush();
    }

    public function getAlertsByPriority(AlertPriority $priority, int $limit = 5): array
    {
        return $this->alertRepository->findBy(
            ['priority' => $priority->value],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function createEquipmentAlert(
        Equipment $equipment,
        string $message,
        AlertCategory $category,
        AlertLevel $level = AlertLevel::INFO,
        AlertPriority $priority = AlertPriority::MEDIUM,
        bool $persistent = true
    ): Alert {
        $alert = new Alert();
        $alert->setMessage($message)
            ->setLevel($level->value)
            ->setPriority($priority->value)
            ->setPersistent($persistent)
            ->setCreatedAt(new \DateTime())
            ->setEquipment($equipment)
            ->setCategory($category->value);

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    public function getEquipmentAlerts(
        ?Equipment $equipment = null,
        ?AlertCategory $category = null,
        int $page = 1,
        int $limit = 10,
        ?AlertLevel $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        return $this->alertRepository->findEquipmentAlerts(
            $equipment,
            $category ? $category->value : null,
            $page,
            $limit,
            $level ? $level->value : null,
            $fromDate,
            $toDate
        );
    }

    public function getEquipmentAlertsCount(
        ?Equipment $equipment = null,
        ?AlertCategory $category = null,
        ?AlertLevel $level = null
    ): int {
        return $this->alertRepository->countEquipmentAlerts($equipment, $category ? $category->value : null, $level ? $level->value : null);
    }

    public function createMaintenanceAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::MAINTENANCE,
            AlertLevel::WARNING,
            AlertPriority::HIGH,
            true
        );
    }

    public function createStockAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::STOCK,
            AlertLevel::WARNING,
            AlertPriority::MEDIUM,
            true
        );
    }

    public function createCalibrationAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::CALIBRATION,
            AlertLevel::INFO,
            AlertPriority::MEDIUM,
            true
        );
    }

    public function createWarrantyAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::WARRANTY,
            AlertLevel::INFO,
            AlertPriority::LOW,
            true
        );
    }
}
