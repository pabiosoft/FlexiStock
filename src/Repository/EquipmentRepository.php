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

    // Add your custom queries here, e.g., to find equipment by status
    public function searchEquipments(array $criteria)
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['brand'])) {
            $qb->andWhere('e.brand = :brand')
                ->setParameter('brand', $criteria['brand']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['category'])) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $criteria['category']);
        }

        return $qb->getQuery()->getResult();
    }
}
