<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use App\Service\NotificationService;
use App\Enum\AlertCategory;
use App\Enum\AlertLevel;
use App\Enum\AlertPriority;
use DateTime;
use Psr\Log\LoggerInterface;

class EquipmentLifecycleService
{
    private const WARNING_DAYS = 30; // Alert 30 days before expiration
    private const CRITICAL_DAYS = 7; // Critical alert 7 days before expiration

    public function __construct(
        private readonly EquipmentRepository $equipmentRepository,
        private readonly NotificationService $notificationService,
        #[Autowired(service: 'monolog.logger.equipment')]
        private readonly LoggerInterface $logger
    ) {}

    public function checkEquipmentLifecycle(): void
    {
        $this->logger->info('Starting equipment lifecycle check');
        $this->checkExpiringEquipment();
        $this->checkExpiredEquipment();
        $this->logger->info('Finished equipment lifecycle check');
    }

    private function checkExpiringEquipment(): void
    {
        $now = new DateTime();
        $expiringEquipment = $this->equipmentRepository->findExpiringItems(self::WARNING_DAYS);
        $this->logger->info('Found {count} expiring equipment', ['count' => count($expiringEquipment)]);

        foreach ($expiringEquipment as $equipment) {
            $daysUntilExpiration = $now->diff($equipment->getWarrantyDate())->days;
            $this->logger->info('Checking equipment {id} with {days} days until expiration', [
                'id' => $equipment->getId(),
                'days' => $daysUntilExpiration
            ]);

            if ($daysUntilExpiration <= self::CRITICAL_DAYS) {
                $this->createCriticalAlert($equipment);
            } elseif ($daysUntilExpiration <= self::WARNING_DAYS) {
                $this->createWarningAlert($equipment);
            }
        }
    }

    private function checkExpiredEquipment(): void
    {
        $now = new DateTime();
        $expiredEquipment = $this->equipmentRepository->findExpiredItems($now);
        $this->logger->info('Found {count} expired equipment', ['count' => count($expiredEquipment)]);

        foreach ($expiredEquipment as $equipment) {
            $this->createExpiredAlert($equipment);
        }
    }

    private function createWarningAlert(Equipment $equipment): void
    {
        $daysLeft = (new DateTime())->diff($equipment->getWarrantyDate())->days;
        $message = sprintf(
            'L\'équipement "%s" (ID: %s) expire dans %d jours. Planifiez son renouvellement.',
            $equipment->getName(),
            $equipment->getId(),
            $daysLeft
        );

        $this->logger->info('Creating warning alert for equipment {id}', ['id' => $equipment->getId()]);
        $this->notificationService->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::WARRANTY,
            AlertLevel::WARNING,
            AlertPriority::MEDIUM,
            true
        );
    }

    private function createCriticalAlert(Equipment $equipment): void
    {
        $daysLeft = (new DateTime())->diff($equipment->getWarrantyDate())->days;
        $message = sprintf(
            'URGENT: L\'équipement "%s" (ID: %s) expire dans %d jours! Action immédiate requise.',
            $equipment->getName(),
            $equipment->getId(),
            $daysLeft
        );

        $this->logger->info('Creating critical alert for equipment {id}', ['id' => $equipment->getId()]);
        $this->notificationService->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::WARRANTY,
            AlertLevel::ERROR,
            AlertPriority::HIGH,
            true
        );
    }

    private function createExpiredAlert(Equipment $equipment): void
    {
        $message = sprintf(
            'L\'équipement "%s" (ID: %s) est expiré! Veuillez le retirer de l\'inventaire.',
            $equipment->getName(),
            $equipment->getId()
        );

        $this->logger->info('Creating expired alert for equipment {id}', ['id' => $equipment->getId()]);
        $this->notificationService->createEquipmentAlert(
            $equipment,
            $message,
            AlertCategory::WARRANTY,
            AlertLevel::ERROR,
            AlertPriority::HIGH,
            true
        );
    }

    public function checkMaintenanceSchedule(): void
    {
        $this->logger->info('Starting maintenance schedule check');
        $now = new DateTime();
        $warningDate = (clone $now)->modify('+' . self::WARNING_DAYS . ' days');

        $equipmentNeedingMaintenance = $this->equipmentRepository->findUpcomingMaintenance($now, $warningDate);
        $this->logger->info('Found {count} equipment needing maintenance', ['count' => count($equipmentNeedingMaintenance)]);

        foreach ($equipmentNeedingMaintenance as $equipment) {
            $daysUntilMaintenance = $now->diff($equipment->getNextMaintenanceDate())->days;
            $this->logger->info('Equipment {id} needs maintenance in {days} days', [
                'id' => $equipment->getId(),
                'days' => $daysUntilMaintenance
            ]);

            $message = sprintf(
                'Maintenance prévue pour l\'équipement "%s" (ID: %s) dans %d jours.',
                $equipment->getName(),
                $equipment->getId(),
                $daysUntilMaintenance
            );

            $level = $daysUntilMaintenance <= self::CRITICAL_DAYS 
                ? AlertLevel::ERROR 
                : AlertLevel::WARNING;

            $priority = $daysUntilMaintenance <= self::CRITICAL_DAYS
                ? AlertPriority::HIGH
                : AlertPriority::MEDIUM;

            $this->notificationService->createEquipmentAlert(
                $equipment,
                $message,
                AlertCategory::MAINTENANCE,
                $level,
                $priority,
                true
            );
        }

        $this->logger->info('Finished maintenance schedule check');
    }
}
