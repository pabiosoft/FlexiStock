<?php

namespace App\Repository;

use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use App\Event\EquipmentEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DateTime;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EquipmentRepository extends ServiceEntityRepository
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($registry, Equipment::class);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatchEvent(Equipment $equipment, string $eventName): void
    {
        $equipment->setSuppressEvents(true);
        try {
            $this->eventDispatcher->dispatch(new EquipmentEvent($equipment), $eventName);
        } finally {
            $equipment->setSuppressEvents(false);
        }
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?Equipment
    {
        $equipment = parent::find($id, $lockMode, $lockVersion);
        if ($equipment) {
            $equipment->setRepository($this);
        }
        return $equipment;
    }

    public function findAll(): array
    {
        $equipment = parent::findAll();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        $equipment = parent::findBy($criteria, $orderBy, $limit, $offset);
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?Equipment
    {
        $equipment = parent::findOneBy($criteria, $orderBy);
        if ($equipment) {
            $equipment->setRepository($this);
        }
        return $equipment;
    }

    public function findLowStockItems(): array
    {
        $equipment = $this->createQueryBuilder('e')
            ->where('e.stockQuantity <= e.minThreshold')
            ->andWhere('e.status = :status')
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function findExpiringItems(int $daysThreshold = 30): array
    {
        $thresholdDate = new \DateTime("+{$daysThreshold} days");

        $equipment = $this->createQueryBuilder('e')
            ->where('e.warrantyDate IS NOT NULL')
            ->andWhere('e.warrantyDate <= :thresholdDate')
            ->andWhere('e.warrantyDate >= :today')
            ->andWhere('e.status = :status')
            ->setParameter('thresholdDate', $thresholdDate)
            ->setParameter('today', new \DateTime())
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function getPaginatedEquipment(int $page, int $limit, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c');

        // Apply filters
        if (!empty($criteria['name'])) {
            $qb->andWhere('e.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', (int)$criteria['category']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['lowStock']) && $criteria['lowStock'] === true) {
            $qb->andWhere('e.stockQuantity <= e.minThreshold');
        }

        // Get total items before pagination
        $totalItems = count($qb->getQuery()->getResult());

        // Add pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit)
           ->orderBy('e.createdAt', 'DESC');

        // Get paginated results
        $items = $qb->getQuery()->getResult();

        // Calculate pagination details
        $pageCount = ceil($totalItems / $limit);

        return [
            'items' => $items,
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'totalItems' => $totalItems,
            'itemsPerPage' => $limit
        ];
    }

    public function getStockValueReport(): array
    {
        $equipment = $this->createQueryBuilder('e')
            ->select('e.name', 'e.stockQuantity', 'e.price', 
                    '(e.stockQuantity * e.price) as totalValue')
            ->where('e.status = :status')
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function getMovementHistory(Equipment $equipment): array
    {
        $equipment = $this->createQueryBuilder('e')
            ->select('m')
            ->join('e.movements', 'm')
            ->where('e.id = :equipmentId')
            ->setParameter('equipmentId', $equipment->getId())
            ->orderBy('m.movementDate', 'DESC')
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
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

        $equipment = $qb->getQuery()->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function getDashboardStats(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id) as total')
            ->addSelect('SUM(CASE WHEN e.status = :active THEN 1 ELSE 0 END) as active')
            ->addSelect('SUM(CASE WHEN e.stockQuantity <= e.minThreshold THEN 1 ELSE 0 END) as lowStock')
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

    public function findExpiredItems(DateTime $currentDate): array
    {
        $equipment = $this->createQueryBuilder('e')
            ->where('e.warrantyDate < :currentDate')
            ->andWhere('e.status = :status')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function findUpcomingMaintenance(DateTime $currentDate, DateTime $warningDate): array
    {
        $equipment = $this->createQueryBuilder('e')
            ->where('e.nextMaintenanceDate BETWEEN :currentDate AND :warningDate')
            ->andWhere('e.status = :status')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('warningDate', $warningDate)
            ->setParameter('status', EquipmentStatus::ACTIVE)
            ->getQuery()
            ->getResult();
        foreach ($equipment as $item) {
            $item->setRepository($this);
        }
        return $equipment;
    }

    public function findBySearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c');

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

        return $qb->getQuery()->getResult();
    }
}