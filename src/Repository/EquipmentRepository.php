<?php

namespace App\Repository;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    public function findLowStockItems(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.stockQuantity <= e.minThreshold')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    public function findExpiringItems(int $daysThreshold = 30): array
    {
        $thresholdDate = new \DateTime("+{$daysThreshold} days");

        return $this->createQueryBuilder('e')
            ->where('e.warrantyDate IS NOT NULL')
            ->andWhere('e.warrantyDate <= :thresholdDate')
            ->andWhere('e.warrantyDate >= :today')
            ->setParameter('thresholdDate', $thresholdDate)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function searchEquipments(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c');

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $criteria['category']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['lowStock']) && $criteria['lowStock']) {
            $qb->andWhere('e.stockQuantity <= e.minThreshold');
        }

        return $qb->getQuery()->getResult();
    }

    public function getStockValueReport(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name', 'e.stockQuantity', 'e.price', 
                    '(e.stockQuantity * e.price) as totalValue')
            ->where('e.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    public function getMovementHistory(Equipment $equipment): array
    {
        return $this->createQueryBuilder('e')
            ->select('m')
            ->join('e.movements', 'm')
            ->where('e.id = :equipmentId')
            ->setParameter('equipmentId', $equipment->getId())
            ->orderBy('m.movementDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}