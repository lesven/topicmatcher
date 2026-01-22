<?php

declare(strict_types=1);

namespace App\Domain\EventManagement;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: EventStatus::class)]
    private EventStatus $status = EventStatus::DRAFT;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, string $slug, ?string $description = null, ?\DateTime $eventDate = null, ?string $location = null)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->eventDate = $eventDate ? \DateTimeImmutable::createFromMutable($eventDate) : null;
        $this->location = $location;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->touch();
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function activate(): void
    {
        if ($this->status === EventStatus::DRAFT) {
            $this->status = EventStatus::ACTIVE;
            $this->touch();
        }
    }

    public function close(): void
    {
        if ($this->status === EventStatus::ACTIVE) {
            $this->status = EventStatus::CLOSED;
            $this->touch();
        }
    }

    public function archive(): void
    {
        if ($this->status === EventStatus::CLOSED) {
            $this->status = EventStatus::ARCHIVED;
            $this->touch();
        }
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    public function allowsSubmissions(): bool
    {
        return $this->status->allowsSubmissions();
    }

    public function allowsInterests(): bool
    {
        return $this->status->allowsInterests();
    }

    public function allowsModeration(): bool
    {
        return $this->status->allowsModeration();
    }

    public function allowsExport(): bool
    {
        return $this->status->allowsExport();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Category>
     */
    /**
     * Wichtige MVP-Regel: Draft-Events sind immer leer
     */
    public function isDraftAndEmpty(): bool
    {
        return $this->status === EventStatus::DRAFT;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}