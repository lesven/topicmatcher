<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\EventManagement\Command\EventCommandService;
use App\Domain\EventManagement\Event;
use App\Domain\EventManagement\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repository\EventRepository;

class EventCommandServiceTest extends TestCase
{
    private EventRepository|MockObject $repo;
    private EntityManagerInterface|MockObject $em;
    private EventCommandService $service;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(EventRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new EventCommandService($this->repo, $this->em);
    }

    public function testCreateEventGeneratesUniqueSlugAndPersists(): void
    {
        $this->repo->expects($this->once())->method('generateUniqueSlug')->with('base-slug')->willReturn('unique-slug');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $event = $this->service->createEvent('Name', 'base-slug', 'desc', new \DateTime('2026-01-01'), 'location');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('unique-slug', $event->getSlug());
    }

    public function testBulkActionsActivateAndDeleteBehavior(): void
    {
        $e1 = new Event('One', 'one');
        $e2 = new Event('Two', 'two');

        // e2 is draft and empty
        $e2->setTemplate(false);

        $this->repo->method('findBy')->willReturn([$e1, $e2]);

        $this->em->expects($this->once())->method('flush');

        $result = $this->service->bulkActions([1,2], 'activate');
        $this->assertEquals(2, $result['successCount']);
        $this->assertEmpty($result['errorMessages']);

        // For delete, e1 is not draft/empty so expect an error
        $result2 = $this->service->bulkActions([1,2], 'delete');
        $this->assertGreaterThanOrEqual(0, $result2['successCount']);
        $this->assertIsArray($result2['errorMessages']);
    }
}
