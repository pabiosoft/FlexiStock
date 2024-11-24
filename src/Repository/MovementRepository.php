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
        $qb = $this->createQueryBuilder('m')
            ->where('m.movementDate >= :date')
            ->setParameter('date', new \DateTime('-7 days'))
            ->orderBy('m.movementDate', 'ASC');

        $movements = $qb->getQuery()->getResult();

        $result = [];
        foreach ($movements as $movement) {
            $date = $movement->getMovementDate()->format('Y-m-d');
            if (!isset($result[$date])) {
                $result[$date] = ['in' => 0, 'out' => 0];
            }
            if ($movement->getType() === 'IN') {
                $result[$date]['in'] += $movement->getQuantity();
            } else {
                $result[$date]['out'] += $movement->getQuantity();
            }
        }

        return $result;
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
        $movements = $this->createQueryBuilder('m')
            ->where('m.equipment = :equipment')
            ->andWhere('m.movementDate BETWEEN :startDate AND :endDate')
            ->setParameters([
                'equipment' => $equipment,
                'startDate' => $startDate,
                'endDate' => $endDate
            ])
            ->orderBy('m.movementDate', 'DESC')
            ->getQuery()
            ->getResult();

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

    public function getMovementStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.type')
            ->addSelect('SUM(m.quantity) as total_quantity')
            ->where('m.movementDate BETWEEN :startDate AND :endDate')
            ->setParameters([
                'startDate' => $startDate,
                'endDate' => $endDate
            ])
            ->groupBy('m.type');

        $results = $qb->getQuery()->getResult();

        $stats = ['IN' => 0, 'OUT' => 0];
        foreach ($results as $result) {
            $stats[$result['type']] = (int)$result['total_quantity'];
        }

        return $stats;
    }
}