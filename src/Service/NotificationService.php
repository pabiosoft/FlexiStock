<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Equipment;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_SUCCESS = 'success';

    public const CATEGORY_MAINTENANCE = 'maintenance';
    public const CATEGORY_STOCK = 'stock';
    public const CATEGORY_CALIBRATION = 'calibration';
    public const CATEGORY_WARRANTY = 'warranty';

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
        string $level = self::LEVEL_INFO,
        string $priority = self::PRIORITY_MEDIUM,
        bool $persistent = false
    ): Alert {
        $alert = new Alert();
        $alert->setMessage($message);
        $alert->setLevel($level);
        $alert->setPriority($priority);
        $alert->setPersistent($persistent);
        $alert->setCreatedAt(new \DateTime());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    public function getUnreadAlerts(
        int $page = 1,
        int $limit = 10,
        ?string $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        return $this->alertRepository->findUnreadAlerts($page, $limit, $level, $fromDate, $toDate);
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

    public function getUnreadCount(?string $level = null): int
    {
        return $this->alertRepository->countUnreadAlerts($level);
    }

    public function clearAllNonPersistent(): void
    {
        $alerts = $this->alertRepository->findBy(['persistent' => false]);
        foreach ($alerts as $alert) {
            $this->entityManager->remove($alert);
        }
        $this->entityManager->flush();
    }

    public function getAlertsByPriority(string $priority, int $limit = 5): array
    {
        return $this->alertRepository->findBy(
            ['priority' => $priority],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function createEquipmentAlert(
        Equipment $equipment,
        string $message,
        string $category,
        string $level = self::LEVEL_INFO,
        string $priority = self::PRIORITY_MEDIUM,
        bool $persistent = true
    ): Alert {
        $alert = new Alert();
        $alert->setMessage($message);
        $alert->setLevel($level);
        $alert->setPriority($priority);
        $alert->setPersistent($persistent);
        $alert->setCreatedAt(new \DateTime());
        $alert->setEquipment($equipment);
        $alert->setCategory($category);

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    public function getEquipmentAlerts(
        ?Equipment $equipment = null,
        ?string $category = null,
        int $page = 1,
        int $limit = 10,
        ?string $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        return $this->alertRepository->findEquipmentAlerts(
            $equipment,
            $category,
            $page,
            $limit,
            $level,
            $fromDate,
            $toDate
        );
    }

    public function getEquipmentAlertsCount(
        ?Equipment $equipment = null,
        ?string $category = null,
        ?string $level = null
    ): int {
        return $this->alertRepository->countEquipmentAlerts($equipment, $category, $level);
    }

    public function createMaintenanceAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            self::CATEGORY_MAINTENANCE,
            self::LEVEL_WARNING,
            self::PRIORITY_HIGH
        );
    }

    public function createStockAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            self::CATEGORY_STOCK,
            self::LEVEL_WARNING,
            self::PRIORITY_MEDIUM
        );
    }

    public function createCalibrationAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            self::CATEGORY_CALIBRATION,
            self::LEVEL_INFO,
            self::PRIORITY_MEDIUM
        );
    }

    public function createWarrantyAlert(Equipment $equipment, string $message): Alert
    {
        return $this->createEquipmentAlert(
            $equipment,
            $message,
            self::CATEGORY_WARRANTY,
            self::LEVEL_INFO,
            self::PRIORITY_LOW
        );
    }
}
