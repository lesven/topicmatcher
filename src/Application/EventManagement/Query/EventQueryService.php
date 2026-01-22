<?php

declare(strict_types=1);

namespace App\Application\EventManagement\Query;

use App\Domain\EventManagement\Event;
use App\Infrastructure\Repository\EventRepository;

readonly class EventQueryService
{
    public function __construct(
        private EventRepository $eventRepository
    ) {
    }

    public function findBySlug(string $slug): ?Event
    {
        return $this->eventRepository->findBySlug($slug);
    }

    /**
     * @return Event[]
     */
    public function findPubliclyVisible(): array
    {
        return $this->eventRepository->findPubliclyVisible();
    }

    /**
     * @return Event[]
     */
    public function findActive(): array
    {
        return $this->eventRepository->findActive();
    }

    /**
     * @return Event[]
     */
    public function findExportable(): array
    {
        return $this->eventRepository->findExportable();
    }
}