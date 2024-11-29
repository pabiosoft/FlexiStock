<?php
namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Finds all root categories (categories without a parent)
     *
     * @return Category[]
     */
    public function findRootCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all child categories of a given parent category
     *
     * @param Category $parentCategory
     * @return Category[]
     */
    public function findChildCategories(Category $parentCategory): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parentCategory)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds categories by search query, sorted by specified field and direction
     *
     * @param string|null $search
     * @param string|null $sortBy
     * @param string $direction
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findBySearch(?string $search = null, ?string $sortBy = 'name', string $direction = 'ASC', int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.parent', 'p');

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Add sorting
        switch ($sortBy) {
            case 'id':
                $qb->orderBy('c.id', $direction);
                break;
            case 'parent':
                $qb->orderBy('p.name', $direction);
                break;
            case 'order':
                $qb->orderBy('c.categoryOrder', $direction);
                break;
            case 'name':
            default:
                $qb->orderBy('c.name', $direction);
        }

        // Add pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'items' => $qb->getQuery()->getResult(),
            'totalItems' => $this->getTotalItemsCount($search),
            'currentPage' => $page,
            'itemsPerPage' => $limit,
            'totalPages' => ceil($this->getTotalItemsCount($search) / $limit)
        ];
    }

    private function getTotalItemsCount(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.parent', 'p');

        if ($search) {
            $qb->andWhere('c.name LIKE :search OR p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}