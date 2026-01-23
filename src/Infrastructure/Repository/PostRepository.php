<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Participation\Post;
use App\Domain\Participation\PostStatus;
use App\Domain\EventManagement\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Post entities (read/write operations).
 *
 * Encapsulates common queries used by the application and backoffice.
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    /**
     * PostRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Persist and flush a Post entity.
     *
     * @param Post $post The post to save
     */
    public function save(Post $post): void
    {
        $this->getEntityManager()->persist($post);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove and flush a Post entity.
     *
     * @param Post $post The post to remove
     */
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
            ->andWhere('p.event = :event')
            ->andWhere('p.status = :approved')
            ->setParameter('event', $event)
            ->setParameter('approved', PostStatus::APPROVED)
            ->orderBy('p.createdAt', 'DESC')
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
            ->andWhere('p.event = :event')
            ->andWhere('p.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Zähle Posts für ein Event nach Status.
     *
     * @param Event $event Das Event
     * @param PostStatus $status Der Status
     * @return int Anzahl der Posts
     */
    public function countByEventAndStatus(Event $event, PostStatus $status): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.event = :event')
            ->andWhere('p.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Zähle alle Posts.
     *
     * @return int Gesamtanzahl der Posts
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Zähle Posts nach Status.
     *
     * @param PostStatus $status Zu zählender Status
     * @return int Anzahl der Posts mit diesem Status
     */
    public function getCountByStatus(PostStatus $status): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Post[]
     */
    public function findByStatus(PostStatus $status, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('p.event', 'e')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Post[]
     */
    public function getRecentlyModerated(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->join('p.event', 'e')
            ->andWhere('p.status IN (:statuses)')
            ->andWhere('p.updatedAt IS NOT NULL')
            ->setParameter('statuses', [PostStatus::APPROVED, PostStatus::REJECTED])
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Zähle Posts für ein bestimmtes Event.
     *
     * @param Event $event Das Event
     * @return int Anzahl der Posts
     */
    public function countByEvent(Event $event): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Post[]
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('p.event = :event')
            ->setParameter('event', $event)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}