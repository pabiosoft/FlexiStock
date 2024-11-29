<?php

namespace App\Repository;

use App\Entity\MaintenanceRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceRecord>
 */
class MaintenanceRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceRecord::class);
    }

    public function findUpcomingMaintenance(\DateTimeInterface $date = null)
    {
        if (!$date) {
            $date = new \DateTime();
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.nextMaintenanceDate <= :date')
            ->setParameter('date', $date)
            ->orderBy('m.nextMaintenanceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaintenanceHistory(int $equipmentId)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.equipment = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('m.maintenanceDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingMaintenanceInDays(int $days)
    {
        $today = new \DateTime();
        $futureDate = new \DateTime("+{$days} days");

        return $this->createQueryBuilder('m')
            ->andWhere('m.nextMaintenanceDate BETWEEN :today AND :futureDate')
            ->andWhere('m.status != :completed')
            ->setParameter('today', $today)
            ->setParameter('futureDate', $futureDate)
            ->setParameter('completed', 'completed')
            ->orderBy('m.nextMaintenanceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueMaintenance()
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('m')
            ->andWhere('m.nextMaintenanceDate < :today')
            ->andWhere('m.status != :completed')
            ->setParameter('today', $today)
            ->setParameter('completed', 'completed')
            ->orderBy('m.nextMaintenanceDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaintenanceStats(int $equipmentId)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT 
                COUNT(*) as total_records,
                AVG(cost) as average_cost,
                MAX(maintenance_date) as last_maintenance,
                MIN(maintenance_date) as first_maintenance,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = \'in_progress\' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(cost) as total_cost
            FROM maintenance_record
            WHERE equipment_id = :equipmentId
        ';

        $result = $connection->executeQuery($sql, ['equipmentId' => $equipmentId])->fetchAssociative();
        return $result ?: [
            'total_records' => 0,
            'average_cost' => 0,
            'last_maintenance' => null,
            'first_maintenance' => null,
            'completed_count' => 0,
            'in_progress_count' => 0,
            'total_cost' => 0
        ];
    }
}
