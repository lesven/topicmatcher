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

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Post::class)]
    private Collection $posts;

    /**
     * Erzeugt eine neue Kategorie für ein Event.
     *
     * @param Event $event Das zugehörige Event
     * @param string $name Name der Kategorie
     * @param string $color Farbcode der Kategorie (z.B. '#ff0000')
     * @param string|null $description Optionale Beschreibung
     */
    public function __construct(Event $event, string $name, string $color, ?string $description = null)
    {
        $this->event = $event;
        $this->name = $name;
        $this->color = $color;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
    }

    /**
     * Gibt die ID der Kategorie zurück.
     *
     * @return int|null Die ID oder null wenn nicht persistiert
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Liefert den Namen der Kategorie.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Setzt den Namen der Kategorie.
     *
     * @param string $name Neuer Name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Liefert die optionale Beschreibung der Kategorie.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Setzt eine optionale Beschreibung.
     *
     * @param string|null $description Beschreibung oder null
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Liefert den Farbcode der Kategorie.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Setzt den Farbcode der Kategorie.
     *
     * @param string|null $color Farbcode oder null
     */
    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    /**
     * Liefert die Sortierreihenfolge der Kategorie.
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * Setzt die Sortierreihenfolge.
     *
     * @param int $sortOrder Neue Reihenfolge
     */
    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * Zeitpunkt der Erstellung der Kategorie.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Liefert alle Posts dieser Kategorie.
     *
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * Fügt einen Post zur Kategorie hinzu und setzt die Zuordnung am Post.
     *
     * @param Post $post Der hinzuzufügende Post
     */
    public function addPost(Post $post): void
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategory($this);
        }
    }

    /**
     * Entfernt einen Post aus der Kategorie und löst die Zuordnung am Post.
     *
     * @param Post $post Zu entfernender Post
     */
    public function removePost(Post $post): void
    {
        if ($this->posts->removeElement($post)) {
            $post->setCategory(null);
        }
    }

    /**
     * Gibt die Anzahl approbierter Posts in dieser Kategorie zurück.
     */
    public function getApprovedPostsCount(): int
    {
        return $this->posts->filter(fn(Post $post) => $post->getStatus() === PostStatus::APPROVED)->count();
    }

    /**
     * Liefert das zugehörige Event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * Setzt das zugehörige Event.
     *
     * @param Event $event Neues Event
     */
    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }
}