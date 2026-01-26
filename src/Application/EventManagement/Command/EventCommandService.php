<?php

declare(strict_types=1);

namespace App\Application\EventManagement\Command;

use App\Domain\EventManagement\Event;
use App\Infrastructure\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class EventCommandService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Create and persist a new Event, ensuring slug uniqueness.
     *
     * @param string $name
     * @param string $slugBase
     * @param string|null $description
     * @param \DateTime|null $eventDate
     * @param string|null $location
     * @param bool $makeTemplate
     * @return Event
     */
    public function createEvent(string $name, string $slugBase, ?string $description = null, ?\DateTime $eventDate = null, ?string $location = null, bool $makeTemplate = false): Event
    {
        $uniqueSlug = $this->eventRepository->generateUniqueSlug($slugBase);

        $event = new Event($name, $uniqueSlug, $description, $eventDate, $location);
        if ($makeTemplate) {
            $event->setTemplate(true);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    /**
     * Perform bulk actions on multiple events.
     *
     * @param array<int> $eventIds
     * @param string $action activate|close|archive|delete
     * @return array{successCount:int,errorMessages:string[]}
     */
    public function bulkActions(array $eventIds, string $action): array
    {
        if (empty($eventIds)) {
            return ['successCount' => 0, 'errorMessages' => ['Keine Events ausgewählt.']];
        }

        $events = $this->eventRepository->findBy(['id' => $eventIds]);

        if (count($events) !== count($eventIds)) {
            return ['successCount' => 0, 'errorMessages' => ['Einige Events wurden nicht gefunden.']];
        }

        $successCount = 0;
        $errors = [];

        foreach ($events as $event) {
            try {
                switch ($action) {
                    case 'activate':
                        $event->activate();
                        $successCount++;
                        break;
                    case 'close':
                        $event->close();
                        $successCount++;
                        break;
                    case 'archive':
                        $event->archive();
                        $successCount++;
                        break;
                    case 'delete':
                        if (!$event->isDraftAndEmpty()) {
                            $errors[] = sprintf('Event "%s" kann nicht gelöscht werden (nicht leer oder nicht im Draft-Status).', $event->getName());
                            continue 2; // continue outer loop
                        }
                        $this->entityManager->remove($event);
                        $successCount++;
                        break;
                    default:
                        return ['successCount' => 0, 'errorMessages' => ['Unbekannte Aktion.']];
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Event "%s": %s', $event->getName(), $e->getMessage());
            }
        }

        $this->entityManager->flush();

        return ['successCount' => $successCount, 'errorMessages' => $errors];
    }
}
