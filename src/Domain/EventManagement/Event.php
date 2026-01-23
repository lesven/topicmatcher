<?php

declare(strict_types=1);

namespace App\Domain\EventManagement;

use App\Domain\Participation\Category;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Domain aggregate representing an Event (conference/trade show).
 *
 * Manages event lifecycle (draft -> active -> closed -> archived) and categories.
 */
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

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isTemplate = false;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Event $templateSource = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Category::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'name' => 'ASC'])]
    private Collection $categories;

    /**
     * Erstellt ein neues Event.
     *
     * @param string $name Name des Events
     * @param string $slug URL/Slug des Events
     * @param string|null $description Optionale Beschreibung
     * @param \DateTime|null $eventDate Optionales Veranstaltungsdatum (mutable)
     * @param string|null $location Optionaler Ort
     */
    public function __construct(string $name, string $slug, ?string $description = null, ?\DateTime $eventDate = null, ?string $location = null)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->eventDate = $eventDate ? \DateTimeImmutable::createFromMutable($eventDate) : null;
        $this->location = $location;
        $this->createdAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
    }

    /**
     * Gibt die ID des Events zurück.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Liefert den Namen des Events.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Setzt den Namen des Events und aktualisiert den Zeitstempel.
     *
     * @param string $name Neuer Name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    /**
     * Liefert die optionale Beschreibung des Events.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Setzt die Beschreibung und aktualisiert den Zeitstempel.
     *
     * @param string|null $description Neue Beschreibung
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    /**
     * Liefert den Slug des Events.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Setzt den Slug und aktualisiert den Zeitstempel.
     *
     * @param string $slug Neuer Slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->touch();
    }

    /**
     * Liefert den aktuellen Status des Events.
     */
    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    /**
     * Liefert das Veranstaltungsdatum oder null.
     */
    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    /**
     * Liefert den Ort des Events oder null.
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Aktiviert das Event wenn es sich im Entwurfsstatus befindet.
     */
    public function activate(): void
    {
        if ($this->status === EventStatus::DRAFT) {
            $this->status = EventStatus::ACTIVE;
            $this->touch();
        }
    }

    /**
     * Schließt das Event, sofern es aktiv ist.
     */
    public function close(): void
    {
        if ($this->status === EventStatus::ACTIVE) {
            $this->status = EventStatus::CLOSED;
            $this->touch();
        }
    }

    /**
     * Archiviert das Event, wenn es geschlossen wurde.
     */
    public function archive(): void
    {
        if ($this->status === EventStatus::CLOSED) {
            $this->status = EventStatus::ARCHIVED;
            $this->touch();
        }
    }

    /**
     * Prüft ob das Event öffentlich sichtbar ist (Statusabhängig).
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    /**
     * Prüft ob Einreichungen für dieses Event erlaubt sind.
     */
    public function allowsSubmissions(): bool
    {
        return $this->status->allowsSubmissions();
    }

    /**
     * Prüft ob Interessenbekundungen für dieses Event erlaubt sind.
     */
    public function allowsInterests(): bool
    {
        return $this->status->allowsInterests();
    }

    /**
     * Prüft ob Moderation für dieses Event erlaubt ist.
     */
    public function allowsModeration(): bool
    {
        return $this->status->allowsModeration();
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * Fügt eine Kategorie zum Event hinzu und setzt die Rückreferenz.
     *
     * @param Category $category Zu ergänzende Kategorie
     */
    public function addCategory(Category $category): void
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setEvent($this);
            $this->touch();
        }
    }

    /**
     * Entfernt eine Kategorie vom Event.
     *
     * @param Category $category Zu entfernende Kategorie
     */
    public function removeCategory(Category $category): void
    {
        if ($this->categories->removeElement($category)) {
            $this->touch();
        }
    }

    /**
     * Anzahl der Kategorien dieses Events.
     */
    public function getCategoriesCount(): int
    {
        return $this->categories->count();
    }

    /**
     * Prüft ob ein Export für dieses Event erlaubt ist.
     */
    public function allowsExport(): bool
    {
        return $this->status->allowsExport();
    }

    /**
     * Zeitpunkt der Erstellung des Events.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Zeitpunkt der letzten Aktualisierung oder null.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Prüft ob dieses Event als Template markiert ist.
     */
    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    /**
     * Markiert oder entfernt das Template-Flag und aktualisiert Zeitstempel.
     *
     * @param bool $isTemplate true wenn Template
     */
    public function setTemplate(bool $isTemplate): void
    {
        $this->isTemplate = $isTemplate;
        $this->touch();
    }

    /**
     * Liefert die Quelle dieses Templates oder null.
     */
    public function getTemplateSource(): ?Event
    {
        return $this->templateSource;
    }

    /**
     * Setzt die Template-Quelle dieses Events.
     *
     * @param Event|null $templateSource Quell-Event oder null
     */
    public function setTemplateSource(?Event $templateSource): void
    {
        $this->templateSource = $templateSource;
    }

    /**
     * Erzeugt eine Duplikat dieses Events (z.B. für Templates).
     *
     * @param string $newName Name des neuen Events
     * @param string $newSlug Einzigartiger Slug für das neue Event
     * @param bool $copyCategories Ob Kategorien kopiert werden sollen
     * @return Event Das neu erstellte Event
     */
    public function createDuplicate(string $newName, string $newSlug, bool $copyCategories = true): Event
    {
        $duplicate = new Event(
            $newName,
            $newSlug,
            $this->description,
            null, // Reset event date for new event
            $this->location
        );
        
        $duplicate->setTemplateSource($this);
        
        if ($copyCategories) {
            foreach ($this->categories as $category) {
                $newCategory = new Category(
                    $duplicate,
                    $category->getName(),
                    $category->getColor(),
                    $category->getDescription()
                );
                $newCategory->setSortOrder($category->getSortOrder());
                $duplicate->addCategory($newCategory);
            }
        }
        
        return $duplicate;
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

    /**
     * Aktualisiert den Zeitstempel bei Änderungen.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}