<?php

declare(strict_types=1);

namespace App\Domain\Backoffice;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'backoffice_users')]
#[ORM\UniqueConstraint(name: 'unique_email', columns: ['email'])]
class BackofficeUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $password;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mustChangePassword = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;

    /**
     * Erstellt einen Backoffice-Benutzer.
     *
     * @param string $email Eindeutige E-Mail-Adresse
     * @param string $name Anzeigename
     * @param UserRole $role Rolle des Nutzers
     */
    public function __construct(string $email, string $name, UserRole $role)
    {
        $this->email = $email;
        $this->name = $name;
        $this->role = $role;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Gibt die ID des Backoffice-Benutzers zurück.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Liefert die E-Mail-Adresse des Benutzers.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Setzt die E-Mail-Adresse des Benutzers.
     *
     * @param string $email Neue E-Mail
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Liefert den Anzeigenamen des Benutzers.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Setzt den Anzeigenamen des Benutzers.
     *
     * @param string $name Neuer Name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Liefert das (gehashte) Passwort des Benutzers.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Setzt das (gehashte) Passwort und aktualisiert den Änderungszeitpunkt.
     *
     * @param string $password Gehashtes Passwort
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->passwordChangedAt = new \DateTimeImmutable();
        
        // Reset mustChangePassword when password is set
        if ($this->mustChangePassword) {
            $this->mustChangePassword = false;
        }
    }

    /**
     * Liefert die Rolle des Benutzers.
     */
    public function getRole(): UserRole
    {
        return $this->role;
    }

    /**
     * Setzt die Rolle des Benutzers.
     *
     * @param UserRole $role Neue Rolle
     */
    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }

    /**
     * Prüft ob der Benutzer aktiv ist.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Aktiviert den Benutzer.
     */
    public function activate(): void
    {
        $this->isActive = true;
    }

    /**
     * Deaktiviert den Benutzer.
     */
    public function deactivate(): void
    {
        $this->isActive = false;
    }

    /**
     * Prüft ob der Benutzer das Passwort ändern muss.
     */
    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    /**
     * Erzwingt, dass der Benutzer sein Passwort ändern muss.
     */
    public function forcePasswordChange(): void
    {
        $this->mustChangePassword = true;
    }

    /**
     * Zeitpunkt der Erstellung des Benutzers.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Zeitpunkt des letzten Logins oder null.
     */
    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Aktualisiert den Zeitpunkt des letzten Logins auf jetzt.
     */
    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
    }

    /**
     * Zeitpunkt der letzten Passwortänderung oder null.
     */
    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    // Symfony UserInterface implementation

    /**
     * Rückgabe des eindeutigen Benutzerschlüssels für Symfony (E-Mail).
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Gibt die Rollen für das Security-System zurück.
     *
     * @return string[] Array von Rollen
     */
    public function getRoles(): array
    {
        return [$this->role->value];
    }

    /**
     * Entfernt temporäre sensible Daten (falls vorhanden).
     */
    public function eraseCredentials(): void
    {
        // Nothing to erase for now
    }

    // Permission helpers

    /**
     * Prüft ob der Benutzer Events verwalten darf.
     */
    public function canManageEvents(): bool
    {
        return $this->isActive && $this->role->canManageEvents();
    }

    /**
     * Prüft ob der Benutzer andere Nutzer verwalten darf.
     */
    public function canManageUsers(): bool
    {
        return $this->isActive && $this->role->canManageUsers();
    }

    /**
     * Prüft ob der Benutzer moderieren darf.
     */
    public function canModerate(): bool
    {
        return $this->isActive && $this->role->canModerate();
    }

    /**
     * Prüft ob der Benutzer exportieren darf.
     */
    public function canExport(): bool
    {
        return $this->isActive && $this->role->canExport();
    }
}