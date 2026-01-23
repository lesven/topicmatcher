<?php

declare(strict_types=1);

namespace App\Application\EventManagement\Query;

use App\Domain\EventManagement\Event;
use App\Infrastructure\Repository\EventRepository;

readonly class EventQueryService
{
    /**
     * EventQueryService constructor.
     *
     * @param EventRepository $eventRepository Repository for Event read operations
     */
    public function __construct(
        private EventRepository $eventRepository
    ) {
    }

    /**
     * Findet ein Event anhand seines Slugs.
     *
     * @param string $slug Event-Slug
     * @return Event|null Gefundenes Event oder null
     */
    public function findBySlug(string $slug): ?Event
    {
        return $this->eventRepository->findBySlug($slug);
    }

    /**
     * Liefert Events, die öffentlich sichtbar sind.
     *
     * @return Event[] Liste öffentlich sichtbarer Events
     */
    public function findPubliclyVisible(): array
    {
        return $this->eventRepository->findPubliclyVisible();
    }

    /**
     * Liefert aktuell aktive Events.
     *
     * @return Event[] Liste aktiver Events
     */
    public function findActive(): array
    {
        return $this->eventRepository->findActive();
    }

    /**
     * Liefert Events, die für einen Export geeignet sind.
     *
     * @return Event[] Liste exportierbarer Events
     */
    public function findExportable(): array
    {
        return $this->eventRepository->findExportable();
    }
}