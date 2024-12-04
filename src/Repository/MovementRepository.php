<?php

namespace App\Repository;

use App\Entity\Movement;
use App\Entity\Equipment;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class MovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movement::class);
    }

    public function findLastSevenDaysMovements(): array
    {
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify('-6 days');

        return $this->getMovementsByDateRange($startDate, $endDate);
    }

    public function findLastMonthMovements(): array
    {
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify('-1 month');

        return $this->getMovementsByDateRange($startDate, $endDate);
    }

    private function getMovementsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.movementDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('end', $endDate->format('Y-m-d 23:59:59'))
            ->orderBy('m.movementDate', 'ASC');

        $movements = $qb->getQuery()->getResult();

        // Create a date period for all dates in range
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->modify('+1 day')
        );

        $movementData = [];
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $movementData[$dateStr] = [
                'in' => 0,
                'out' => 0
            ];
        }

        // Fill in actual movement data
        foreach ($movements as $movement) {
            $dateStr = $movement->getMovementDate()->format('Y-m-d');
            $type = strtolower($movement->getType());
            if (isset($movementData[$dateStr])) {
                $movementData[$dateStr][$type] += $movement->getQuantity();
            }
        }

        return $movementData;
    }

    /**
     * Find movements for a specific equipment within a date range
     *
     * @param Equipment $equipment The equipment to find movements for
     * @param \DateTime $startDate Start date of the range
     * @param \DateTime $endDate End date of the range
     * @return ArrayCollection Collection of Movement objects
     */
    public function findByEquipmentAndDateRange(Equipment $equipment, \DateTime $startDate, \DateTime $endDate): ArrayCollection
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->andWhere('m.movementDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('m.movementDate', 'DESC');

        $movements = $qb->getQuery()->getResult();

        return new ArrayCollection($movements);
    }

    public function findRecentMovements(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.equipment', 'e')
            ->leftJoin('m.user', 'u')
            ->addSelect('e', 'u')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
            
    }

    public function getMovementStatistics(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.type', 'SUM(m.quantity) as totalQuantity')
            ->where('m.movementDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('m.type');

        $results = $qb->getQuery()->getResult();

        $stats = ['IN' => 0, 'OUT' => 0];
        foreach ($results as $result) {
            $stats[$result['type']] = (int)$result['totalQuantity'];
        }

        return $stats;
    }

    public function findPaginatedMovements(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('m')
            ->orderBy('m.movementDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countMovements(): int
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}