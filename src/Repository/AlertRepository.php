<?php

namespace App\Repository;

use App\Entity\Alert;
use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    //    /**
    //     * @return Alert[] Returns an array of Alert objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Alert
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findUnreadAlerts(
        int $page = 1,
        int $limit = 10,
        ?string $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->where('a.readAt IS NULL')
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC');

        if ($level) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $level);
        }

        if ($fromDate) {
            $qb->andWhere('a.createdAt >= :fromDate')
               ->setParameter('fromDate', $fromDate);
        }

        if ($toDate) {
            $qb->andWhere('a.createdAt <= :toDate')
               ->setParameter('toDate', $toDate);
        }

        // Get total items for pagination
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Add pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'items' => $qb->getQuery()->getResult(),
            'totalItems' => $total,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function countUnreadAlerts(?string $level = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.readAt IS NULL');

        if ($level) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $level);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findEquipmentAlerts(
        ?Equipment $equipment = null,
        ?string $category = null,
        int $page = 1,
        int $limit = 10,
        ?string $level = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.equipment', 'e')
            ->where('a.readAt IS NULL')
            ->andWhere('a.equipment IS NOT NULL');

        if ($equipment) {
            $qb->andWhere('a.equipment = :equipment')
               ->setParameter('equipment', $equipment);
        }

        if ($category) {
            $qb->andWhere('a.category = :category')
               ->setParameter('category', $category);
        }

        if ($level) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $level);
        }

        if ($fromDate) {
            $qb->andWhere('a.createdAt >= :fromDate')
               ->setParameter('fromDate', $fromDate);
        }

        if ($toDate) {
            $qb->andWhere('a.createdAt <= :toDate')
               ->setParameter('toDate', $toDate);
        }

        $qb->orderBy('a.priority', 'DESC')
           ->addOrderBy('a.createdAt', 'DESC');

        // Get total items for pagination
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Add pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'items' => $qb->getQuery()->getResult(),
            'totalItems' => $total,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function countEquipmentAlerts(
        ?Equipment $equipment = null,
        ?string $category = null,
        ?string $level = null
    ): int {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.readAt IS NULL')
            ->andWhere('a.equipment IS NOT NULL');

        if ($equipment) {
            $qb->andWhere('a.equipment = :equipment')
               ->setParameter('equipment', $equipment);
        }

        if ($category) {
            $qb->andWhere('a.category = :category')
               ->setParameter('category', $category);
        }

        if ($level) {
            $qb->andWhere('a.level = :level')
               ->setParameter('level', $level);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
