<?php

declare(strict_types=1);

namespace App\Domain\EventManagement;

use App\Domain\Participation\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Category::class, cascade: ['persist', 'remove'])]
    private Collection $categories;

    public function __construct(string $name, string $slug, ?string $description = null)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
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
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setEvent($this);
        }
    }

    public function removeCategory(Category $category): void
    {
        if ($this->categories->removeElement($category)) {
            $category->setEvent(null);
        }
    }

    /**
     * Wichtige MVP-Regel: Draft-Events sind immer leer
     */
    public function isDraftAndEmpty(): bool
    {
        return $this->status === EventStatus::DRAFT && $this->categories->isEmpty();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}