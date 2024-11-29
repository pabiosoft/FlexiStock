<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function getPaginatedReservations(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $query = $this->createQueryBuilder('r')
            ->leftJoin('r.equipment', 'e')
            ->leftJoin('e.category', 'c')
            ->addSelect('e')
            ->addSelect('c');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->andWhere('r.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->andWhere('c.id = :category_id')
                ->setParameter('category_id', $filters['category']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->andWhere('r.reservationDate BETWEEN :start_date AND :end_date')
                ->setParameter('start_date', new \DateTime($filters['start_date']))
                ->setParameter('end_date', (new \DateTime($filters['end_date']))->modify('+1 day'));
        }

        $query->orderBy('r.reservationDate', 'DESC');

        // Create the pagination
        $paginator = new Paginator($query);

        // Get total items
        $total = count($paginator);

        // Calculate offset
        $pageCount = ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        // Add limit and offset
        $query->setFirstResult($offset)
            ->setMaxResults($limit);

        return [
            'items' => $paginator->getQuery()->getResult(),
            'total' => $total,
            'pageCount' => $pageCount,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countByCategory(int $categoryId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->leftJoin('r.equipment', 'e')
            ->leftJoin('e.category', 'c')
            ->where('c.id = :category_id')
            ->setParameter('category_id', $categoryId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getReservationsByCategory(): array
    {
        return $this->createQueryBuilder('r')
            ->select('c.name as category_name, COUNT(r.id) as reservation_count')
            ->leftJoin('r.equipment', 'e')
            ->leftJoin('e.category', 'c')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.reservationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));

        foreach ($criteria as $field => $value) {
            $qb->andWhere("r.$field = :$field")
               ->setParameter($field, $value);
        }

        return $qb->orderBy('r.reservationDate', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    public function findActiveReservations(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReservations(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.returnDate < :now')
            ->setParameter('status', 'reserved')
            ->setParameter('now', new \DateTime())
            ->orderBy('r.returnDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getReservationStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.reservationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('r.status');

        $results = $qb->getQuery()->getResult();

        $stats = ['reserved' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($results as $result) {
            $stats[$result['status']] = $result['count'];
        }

        return $stats;
    }

    public function findPaginated(int $page = 1, int $limit = 10, array $criteria = []): array
    {
        $query = $this->createQueryBuilder('r')
            ->orderBy('r.reservationDate', 'DESC');

        // Add criteria
        foreach ($criteria as $field => $value) {
            $query->andWhere("r.$field = :$field")
                ->setParameter($field, $value);
        }

        // Get total items before applying pagination
        $countQuery = clone $query;
        $totalItems = $countQuery->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination
        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $query->getQuery()->getResult(),
            'totalItems' => $totalItems,
            'pageCount' => ceil($totalItems / $limit)
        ];
    }

    public function findPaginatedByDateRange(
        \DateTime $startDate,
        \DateTime $endDate,
        int $page = 1,
        int $limit = 10,
        array $criteria = []
    ): array {
        $query = $this->createQueryBuilder('r')
            ->where('r.reservationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.reservationDate', 'DESC');

        // Add additional criteria
        foreach ($criteria as $field => $value) {
            $query->andWhere("r.$field = :$field")
                ->setParameter($field, $value);
        }

        // Get total items before applying pagination
        $countQuery = clone $query;
        $totalItems = $countQuery->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination
        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $query->getQuery()->getResult(),
            'totalItems' => $totalItems,
            'pageCount' => ceil($totalItems / $limit)
        ];
    }

    public function findActiveReservationsUpdated(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('r.reservationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReservationsUpdated(): array
    {
        $today = new \DateTime();
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.returnDate < :today')
            ->setParameter('status', 'active')
            ->setParameter('today', $today)
            ->orderBy('r.returnDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}