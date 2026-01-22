<?php

declare(strict_types=1);

namespace App\Domain\Participation;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'interests')]
#[ORM\UniqueConstraint(name: 'unique_interest_per_post_email', columns: ['post_id', 'email'])]
#[ORM\Index(name: 'idx_interests_created_at', columns: ['created_at'])]
class Interest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $privacyAccepted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'interests')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    /**
     * Erstellt eine Interessenbekundung für einen Post.
     *
     * @param string $name Name der interessierten Person
     * @param string $email E-Mail-Adresse der Person
     * @param bool $privacyAccepted Datenschutzbestätigung
     * @param string|null $message Optionaler Nachrichtentext
     * @param string|null $ipAddress Optional gespeicherte IP
     * @param string|null $userAgent Optionaler User-Agent
     */
    public function __construct(
        string $name,
        string $email,
        bool $privacyAccepted,
        ?string $message = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->privacyAccepted = $privacyAccepted;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Gibt die ID der Interessenbekundung zurück.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Liefert den Namen der interessierten Person.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Setzt den Namen der interessierten Person.
     *
     * @param string $name Neuer Name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Liefert die E-Mail-Adresse der interessierten Person.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Setzt die E-Mail-Adresse der interessierten Person.
     *
     * @param string $email Neue E-Mail
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Liefert die optionale Nachricht.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Setzt die optionale Nachricht.
     *
     * @param string|null $message Nachricht oder null
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * Gibt zurück ob die Datenschutzbedingungen akzeptiert wurden.
     */
    public function isPrivacyAccepted(): bool
    {
        return $this->privacyAccepted;
    }

    /**
     * Zeitpunkt der Erstellung der Interessenbekundung.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Liefert die optionale gespeicherte IP-Adresse.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Setzt die IP-Adresse der Interessenbekundung.
     *
     * @param string|null $ipAddress IP-Adresse oder null
     */
    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Liefert den User-Agent (optional).
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Setzt den User-Agent der Interessenbekundung.
     *
     * @param string|null $userAgent User-Agent oder null
     */
    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Liefert den zugehörigen Post oder null.
     */
    public function getPost(): ?Post
    {
        return $this->post;
    }

    /**
     * Setzt den zugehörigen Post.
     *
     * @param Post|null $post Zu setzender Post oder null
     */
    public function setPost(?Post $post): void
    {
        $this->post = $post;
    }

    /**
     * Überprüft, ob diese Interessenbekundung zu einem bestimmten Event gehört
     */
    public function belongsToEvent(int $eventId): bool
    {
        return $this->post?->getEvent()?->getId() === $eventId;
    }
}