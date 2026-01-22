<?php

declare(strict_types=1);

namespace App\Domain\Participation;

use App\Domain\EventManagement\Event;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
#[ORM\Index(name: 'idx_posts_status', columns: ['status'])]
#[ORM\Index(name: 'idx_posts_created_at', columns: ['created_at'])]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false)]
    private Event $event;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $authorName;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $authorEmail;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $showAuthorName = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $privacyAccepted = false;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PostStatus::class)]
    private PostStatus $status = PostStatus::SUBMITTED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $moderatedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moderationNotes = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Category $category;

    /**
     * @var Collection<int, Interest>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Interest::class, cascade: ['persist', 'remove'])]
    private Collection $interests;

    /**
     * Erstellt einen neuen Post.
     *
     * @param Event $event Zugehöriges Event
     * @param Category $category Zugehörige Kategorie
     * @param string $title Titel des Posts
     * @param string|null $content Inhalt des Posts
     * @param string|null $authorName Anzeigename des Autors
     * @param string $authorEmail E-Mail des Autors
     * @param bool $showAuthorName Sichtbarkeit des Namens
     * @param string $ipAddress IP-Adresse der Erstellung
     * @param string $userAgent User-Agent String
     */
    public function __construct(
        Event $event,
        Category $category,
        string $title,
        ?string $content,
        ?string $authorName,
        string $authorEmail,
        bool $showAuthorName,
        string $ipAddress,
        string $userAgent
    ) {
        $this->event = $event;
        $this->category = $category;
        $this->title = $title;
        $this->content = $content;
        $this->authorName = $authorName;
        $this->authorEmail = $authorEmail;
        $this->showAuthorName = $showAuthorName;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->status = PostStatus::SUBMITTED;
        $this->privacyAccepted = true; // Required for creation
        $this->createdAt = new \DateTimeImmutable();
        $this->interests = new ArrayCollection();
    }

    /**
     * Gibt die ID des Posts zurück.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Liefert das zugehörige Event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Liefert den Titel des Posts.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Liefert den Inhalt des Posts.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Setzt den Inhalt und aktualisiert den Zeitstempel.
     *
     * @param string|null $content Neuer Inhalt
     */
    public function setContent(?string $content): void
    {
        $this->content = $content;
        $this->touch();
    }

    /**
     * Liefert den Namen des Autors (wenn sichtbar).
     */
    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    /**
     * Setzt den Namen des Autors und aktualisiert den Zeitstempel.
     *
     * @param string|null $authorName Neuer Autorname
     */
    public function setAuthorName(?string $authorName): void
    {
        $this->authorName = $authorName;
        $this->touch();
    }

    /**
     * Liefert die (private) E-Mail-Adresse des Autors.
     */
    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    /**
     * Setzt die E-Mail-Adresse des Autors und aktualisiert den Zeitstempel.
     *
     * @param string $authorEmail Neue E-Mail
     */
    public function setAuthorEmail(string $authorEmail): void
    {
        $this->authorEmail = $authorEmail;
        $this->touch();
    }

    /**
     * Gibt zurück ob der Autorenname angezeigt wird.
     */
    public function showAuthorName(): bool
    {
        return $this->showAuthorName;
    }

    /**
     * Setzt die Sichtbarkeit des Autorennamens und aktualisiert den Zeitstempel.
     *
     * @param bool $showAuthorName Sichtbarkeit
     */
    public function setShowAuthorName(bool $showAuthorName): void
    {
        $this->showAuthorName = $showAuthorName;
        $this->touch();
    }

    /**
     * Gibt zurück ob die Datenschutzbedingungen akzeptiert sind.
     */
    public function isPrivacyAccepted(): bool
    {
        return $this->privacyAccepted;
    }

    /**
     * Liefert den aktuellen Moderationsstatus des Posts.
     */
    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    /**
     * Genehmigt den Post durch einen Moderator.
     *
     * @param string $moderatorName Name des Moderators
     */
    public function approve(string $moderatorName): void
    {
        if ($this->status->canBeModerated()) {
            $this->status = PostStatus::APPROVED;
            $this->moderatedBy = $moderatorName;
            $this->moderatedAt = new \DateTimeImmutable();
            $this->touch();
        }
    }

    /**
     * Verwirft den Post durch einen Moderator und speichert optional Notizen.
     *
     * @param string $moderatorName Name des Moderators
     * @param string|null $notes Optionale Moderationsnotizen
     */
    public function reject(string $moderatorName, ?string $notes = null): void
    {
        if ($this->status->canBeModerated()) {
            $this->status = PostStatus::REJECTED;
            $this->moderatedBy = $moderatorName;
            $this->moderatedAt = new \DateTimeImmutable();
            $this->moderationNotes = $notes;
            $this->touch();
        }
    }

    /**
     * Archviert den Post.
     */
    public function archive(): void
    {
        $this->status = PostStatus::ARCHIVED;
        $this->touch();
    }

    /**
     * Prüft ob der Post öffentlich sichtbar ist (abhängig vom Status).
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    /**
     * Prüft ob der Post genehmigt wurde.
     */
    public function isApproved(): bool
    {
        return $this->status === PostStatus::APPROVED;
    }

    /**
     * Zeitpunkt der Erstellung des Posts.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Zeitpunkt der letzten Änderung oder null.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Zeitpunkt der Moderation oder null.
     */
    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    /**
     * Name des Moderators, der die Aktion durchgeführt hat (oder null).
     */
    public function getModeratedBy(): ?string
    {
        return $this->moderatedBy;
    }

    /**
     * Liefert Moderationsnotizen oder null.
     */
    public function getModerationNotes(): ?string
    {
        return $this->moderationNotes;
    }

    /**
     * Setzt Moderationsnotizen und aktualisiert den Zeitstempel.
     *
     * @param string|null $notes Notizen oder null
     */
    public function setModerationNotes(?string $notes): void
    {
        $this->moderationNotes = $notes;
        $this->touch();
    }

    /**
     * Liefert die IP-Adresse des Posts (optional).
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Liefert den User-Agent der Erstellung (optional).
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Liefert die Kategorie dieses Posts.
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * Setzt die Kategorie dieses Posts.
     *
     * @param Category|null $category Neue Kategorie oder null
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Collection<int, Interest>
     */
    public function getInterests(): Collection
    {
        return $this->interests;
    }

    /**
     * Fügt eine Interessenbekundung hinzu und verknüpft sie mit diesem Post.
     *
     * @param Interest $interest Interessensobjekt
     */
    public function addInterest(Interest $interest): void
    {
        if (!$this->interests->contains($interest)) {
            $this->interests->add($interest);
            $interest->setPost($this);
        }
    }

    /**
     * Entfernt eine Interessenbekundung und löst die Zuordnung.
     *
     * @param Interest $interest Zu entfernendes Interesse
     */
    public function removeInterest(Interest $interest): void
    {
        if ($this->interests->removeElement($interest)) {
            $interest->setPost(null);
        }
    }

    /**
     * Gibt die Anzahl Interessenbekundungen für diesen Post zurück.
     */
    public function getInterestCount(): int
    {
        return $this->interests->count();
    }

    /**
     * Überprüft, ob eine E-Mail bereits Interesse bekundet hat
     */
    public function hasInterestFromEmail(string $email): bool
    {
        return $this->interests->exists(
            fn(int $key, Interest $interest) => $interest->getEmail() === $email
        );
    }

    /**
     * Aktualisiert den Zeitstempel für Änderungen.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}