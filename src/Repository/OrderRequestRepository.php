<?php

namespace App\Repository;

use App\Entity\OrderRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderRequest::class);
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));

        foreach ($criteria as $field => $value) {
            $qb->andWhere("o.$field = :$field")
               ->setParameter($field, $value);
        }

        return $qb->orderBy('o.orderDate', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    public function findPendingOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('o.orderDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrdersByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrdersBySupplier(int $supplierId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.supplier = :supplierId')
            ->setParameter('supplierId', $supplierId)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getOrderStatistics(\DateTime $startDate , \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count, SUM(o.totalPrice) as total')
            ->where('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('o.status');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'pending' => ['count' => 0, 'total' => 0],
            'validated' => ['count' => 0, 'total' => 0],
            'processed' => ['count' => 0, 'total' => 0],
            'shipped' => ['count' => 0, 'total' => 0],
            'completed' => ['count' => 0, 'total' => 0],
            'cancelled' => ['count' => 0, 'total' => 0]
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = [
                'count' => $result['count'],
                'total' => $result['total']
            ];
        }

        return $stats;
    }

    public function findLowStockOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.items', 'i')
            ->join('i.equipment', 'e')
            ->where('e.stockQuantity <= e.minThreshold')
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}