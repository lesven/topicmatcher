<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\EventManagement\Event;
use App\Domain\EventManagement\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function remove(Event $event): void
    {
        $this->getEntityManager()->remove($event);
        $this->getEntityManager()->flush();
    }

    public function findBySlug(string $slug): ?Event
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return Event[]
     */
    public function findPubliclyVisible(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status != :archived')
            ->setParameter('archived', EventStatus::ARCHIVED)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :active')
            ->setParameter('active', EventStatus::ACTIVE)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[]
     */
    public function findExportable(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('statuses', [EventStatus::CLOSED, EventStatus::ARCHIVED])
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}