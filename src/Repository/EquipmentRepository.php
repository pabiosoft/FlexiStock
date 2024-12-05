<?php

namespace App\Repository;

use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
            ->setParameter('status', EquipmentStatus::ACTIVE)
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
            ->andWhere('e.status = :status')
            ->setParameter('thresholdDate', $thresholdDate)
            ->setParameter('today', new \DateTime())
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function getPaginatedEquipment(int $page = 1, int $limit = 10, array $criteria = []): array
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

        $qb->orderBy('e.name', 'ASC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'totalItems' => $paginator->count(),
            'itemsPerPage' => $limit,
            'currentPage' => $page,
            'pageCount' => ceil($paginator->count() / $limit)
        ];
    }

    public function getStockValueReport(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name', 'e.stockQuantity', 'e.price', 
                    '(e.stockQuantity * e.price) as totalValue')
            ->where('e.status = :status')
            ->setParameter('status', EquipmentStatus::ACTIVE)
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

    public function findFilteredEquipment(string $searchQuery = '', string $statusFilter = '', string $categoryFilter = '', string $dateFilter = ''): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c');

        if (!empty($searchQuery)) {
            $qb->andWhere('e.name LIKE :searchQuery OR e.serialNumber LIKE :searchQuery')
               ->setParameter('searchQuery', '%' . $searchQuery . '%');
        }

        if (!empty($statusFilter)) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $statusFilter);
        }

        if (!empty($categoryFilter)) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $categoryFilter);
        }

        if (!empty($dateFilter)) {
            $qb->andWhere('e.createdAt >= :date')
               ->setParameter('date', new \DateTime($dateFilter));
        }

        return $qb->getQuery()->getResult();
    }

    public function getDashboardStats(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) as total')
            ->addSelect('SUM(CASE WHEN e.status = :active THEN 1 ELSE 0 END) as active')
            ->addSelect('SUM(CASE WHEN e.stockQuantity <= e.lowStockThreshold THEN 1 ELSE 0 END) as lowStock')
            ->addSelect('SUM(e.stockQuantity * e.price) as totalValue')
            ->setParameter('active', EquipmentStatus::ACTIVE);

        $result = $qb->getQuery()->getOneOrNullResult();

        return [
            'total' => (int)($result['total'] ?? 0),
            'active' => (int)($result['active'] ?? 0),
            'lowStock' => (int)($result['lowStock'] ?? 0),
            'totalValue' => (float)($result['totalValue'] ?? 0),
        ];
    }
}