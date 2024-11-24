<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
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
            ->setParameter('status', 'reserved')
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
}