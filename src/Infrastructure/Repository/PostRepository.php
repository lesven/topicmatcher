<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Participation\Post;
use App\Domain\Participation\PostStatus;
use App\Domain\EventManagement\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    public function remove(Post $post): void
    {
        $this->getEntityManager()->remove($post);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Post[]
     */
    public function findApprovedByEvent(Event $event): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('c.event = :event')
            ->andWhere('p.status = :approved')
            ->setParameter('event', $event)
            ->setParameter('approved', PostStatus::APPROVED)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[]
     */
    public function findSubmittedForModeration(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('c.event', 'e')
            ->andWhere('p.status = :submitted')
            ->andWhere('e.status = :active')
            ->setParameter('submitted', PostStatus::SUBMITTED)
            ->setParameter('active', \App\Domain\EventManagement\EventStatus::ACTIVE)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Post[]
     */
    public function findByEventAndStatus(Event $event, PostStatus $status): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('c.event = :event')
            ->andWhere('p.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByEventAndStatus(Event $event, PostStatus $status): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.category', 'c')
            ->andWhere('c.event = :event')
            ->andWhere('p.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}