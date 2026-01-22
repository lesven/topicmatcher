<?php

declare(strict_types=1);

namespace App\Domain\Participation;

use App\Domain\EventManagement\Event;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'unique_category_per_event', columns: ['event_id', 'name'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Post::class)]
    private Collection $posts;

    public function __construct(string $name, string $color, ?string $description = null)
    {
        $this->name = $name;
        $this->color = $color;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
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
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): void
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategory($this);
        }
    }

    public function removePost(Post $post): void
    {
        if ($this->posts->removeElement($post)) {
            $post->setCategory(null);
        }
    }

    public function getApprovedPostsCount(): int
    {
        return $this->posts->filter(fn(Post $post) => $post->getStatus() === PostStatus::APPROVED)->count();
    }
}