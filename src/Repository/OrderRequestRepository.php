<?php

namespace App\Repository;

use App\Entity\OrderRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderRequest>
 *
 * @method OrderRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderRequest[]    findAll()
 * @method OrderRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderRequest::class);
    }

    public function findPendingOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.orderDate BETWEEN :startDate AND :endDate')
            ->andWhere('o.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', 'completed')
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrdersByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.orderDate', 'DESC')
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

    public function findOrdersByPriority(string $priority): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentOrders(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getOrderStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as total')
            ->addSelect('o.status')
            ->addSelect('SUM(o.totalPrice) as totalAmount')
            ->where('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('o.status');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'totalAmount' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = $result['total'];
            $stats['total'] += $result['total'];
            $stats['totalAmount'] += $result['totalAmount'] ?? 0;
        }

        return $stats;
    }

    public function getOrderVolumeByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.orderDate, COUNT(o.id) as total')
            ->addSelect('SUM(CASE WHEN o.status = :pending THEN 1 ELSE 0 END) as pending')
            ->addSelect('SUM(CASE WHEN o.status = :processing THEN 1 ELSE 0 END) as processing')
            ->addSelect('SUM(CASE WHEN o.status = :completed THEN 1 ELSE 0 END) as completed')
            ->where('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('pending', 'pending')
            ->setParameter('processing', 'processing')
            ->setParameter('completed', 'completed')
            ->groupBy('o.orderDate')
            ->orderBy('o.orderDate', 'ASC');

        $results = $qb->getQuery()->getResult();

        $volumeData = [
            'dates' => [],
            'total' => [],
            'pending' => [],
            'processing' => [],
            'completed' => []
        ];

        foreach ($results as $result) {
            $date = $result['orderDate']->format('d/m');
            $volumeData['dates'][] = $date;
            $volumeData['total'][] = (int)$result['total'];
            $volumeData['pending'][] = (int)$result['pending'];
            $volumeData['processing'][] = (int)$result['processing'];
            $volumeData['completed'][] = (int)$result['completed'];
        }

        return $volumeData;
    }

    public function save(OrderRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}