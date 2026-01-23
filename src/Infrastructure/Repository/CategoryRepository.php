<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Participation\Category;
use App\Domain\EventManagement\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Category entities.
 *
 * Provides helper methods for finding and ordering categories per event.
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Persist a Category and optionally flush.
     *
     * @param Category $category The category to persist
     * @param bool $flush Whether to flush immediately
     */
    public function save(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->persist($category);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a Category and optionally flush.
     *
     * @param Category $category The category to remove
     * @param bool $flush Whether to flush immediately
     */
    public function remove(Category $category, bool $flush = false): void
    {
        $this->getEntityManager()->remove($category);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Category[]
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.event = :event')
            ->setParameter('event', $event)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEventAndName(Event $event, string $name): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.event = :event')
            ->andWhere('c.name = :name')
            ->setParameter('event', $event)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextSortOrder(Event $event): int
    {
        $maxSortOrder = $this->createQueryBuilder('c')
            ->select('MAX(c.sortOrder)')
            ->andWhere('c.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxSortOrder ?? 0) + 10; // Increment by 10 for reordering flexibility
    }

    public function countByEvent(Event $event): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Updates sort order for multiple categories in one transaction
     */
    /**
     * Update sort orders for multiple categories in a single transaction.
     *
     * @param array $categoryIdToSortOrder Map of category id => sort order
     */
    public function updateSortOrders(array $categoryIdToSortOrder): void
    {
        $em = $this->getEntityManager();
        
        foreach ($categoryIdToSortOrder as $categoryId => $sortOrder) {
            $em->createQueryBuilder()
                ->update(Category::class, 'c')
                ->set('c.sortOrder', ':sortOrder')
                ->where('c.id = :categoryId')
                ->setParameter('sortOrder', $sortOrder)
                ->setParameter('categoryId', $categoryId)
                ->getQuery()
                ->execute();
        }
        
        $em->flush();
    }
}