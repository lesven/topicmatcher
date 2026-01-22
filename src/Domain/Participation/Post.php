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

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

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

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Category $category;

    /**
     * @var Collection<int, Interest>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Interest::class, cascade: ['persist', 'remove'])]
    private Collection $interests;

    public function __construct(
        Event $event,
        Category $category,
        string $title,
        string $content,
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->touch();
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): void
    {
        $this->authorName = $authorName;
        $this->touch();
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): void
    {
        $this->authorEmail = $authorEmail;
        $this->touch();
    }

    public function showAuthorName(): bool
    {
        return $this->showAuthorName;
    }

    public function setShowAuthorName(bool $showAuthorName): void
    {
        $this->showAuthorName = $showAuthorName;
        $this->touch();
    }

    public function isPrivacyAccepted(): bool
    {
        return $this->privacyAccepted;
    }

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function approve(string $moderatorName): void
    {
        if ($this->status->canBeModerated()) {
            $this->status = PostStatus::APPROVED;
            $this->moderatedBy = $moderatorName;
            $this->moderatedAt = new \DateTimeImmutable();
            $this->touch();
        }
    }

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

    public function archive(): void
    {
        $this->status = PostStatus::ARCHIVED;
        $this->touch();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    public function isApproved(): bool
    {
        return $this->status === PostStatus::APPROVED;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function getModeratedBy(): ?string
    {
        return $this->moderatedBy;
    }

    public function getModerationNotes(): ?string
    {
        return $this->moderationNotes;
    }

    public function setModerationNotes(?string $notes): void
    {
        $this->moderationNotes = $notes;
        $this->touch();
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

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

    public function addInterest(Interest $interest): void
    {
        if (!$this->interests->contains($interest)) {
            $this->interests->add($interest);
            $interest->setPost($this);
        }
    }

    public function removeInterest(Interest $interest): void
    {
        if ($this->interests->removeElement($interest)) {
            $interest->setPost(null);
        }
    }

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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}