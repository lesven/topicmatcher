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
     * Find an Event by its slug.
     *
     * @param string $slug The event slug
     * @return Event|null The matching Event or null if not found
     */
    public function findBySlug(string $slug): ?Event
    {
        return $this->eventRepository->findBySlug($slug);
    }

    /**
     * Return events visible to the public.
     *
     * @return Event[] List of publicly visible events
     */
    public function findPubliclyVisible(): array
    {
        return $this->eventRepository->findPubliclyVisible();
    }

    /**
     * Return currently active events.
     *
     * @return Event[] List of active events
     */
    public function findActive(): array
    {
        return $this->eventRepository->findActive();
    }

    /**
     * Return events eligible for export.
     *
     * @return Event[] List of exportable events
     */
    public function findExportable(): array
    {
        return $this->eventRepository->findExportable();
    }
}