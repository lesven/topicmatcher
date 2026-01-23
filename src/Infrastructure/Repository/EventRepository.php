<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\EventManagement\Event;
use App\Domain\EventManagement\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Event entities (read/write and query helpers).
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    /**
     * EventRepository constructor.
     */
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

    /**
     * Findet ein Event anhand des Slugs.
     *
     * @param string $slug Event-Slug
     * @return Event|null Gefundenes Event oder null
     */
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

    /**
     * Zähle alle Events.
     *
     * @return int Gesamtanzahl der Events
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Liefert alle Events absteigend nach Erstellungszeit.
     *
     * @return Event[] Liste der Events
     */
    public function findAllOrderedByCreated(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[]
     */
    public function findByStatus(EventStatus $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Anzahl der Events mit einem bestimmten Status.
     *
     * @param EventStatus $status Der zu prüfende Status
     * @return int Anzahl der Events mit diesem Status
     */
    public function getCountByStatus(EventStatus $status): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Liefert alle Events, die als Templates markiert sind.
     *
     * @return Event[] Template-Events
     */
    public function findTemplates(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isTemplate = true')
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liefert Events, die keine Templates sind (reguläre Events).
     *
     * @return Event[] Liste regulärer Events
     */
    public function findNonTemplates(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isTemplate = false')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Erzeugt einen eindeutigen Slug basierend auf einem Basis-Slug. Falls der
     * Slug bereits existiert, wird eine numerische Endung angehängt.
     *
     * @param string $baseSlug Basis-Slug
     * @return string Eindeutiger Slug
     */
    public function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->findOneBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}