<?php

declare(strict_types=1);

namespace App\Domain\Backoffice;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case MODERATOR = 'ROLE_MODERATOR';

    /**
     * Gibt zurück ob diese Rolle Events verwalten darf.
     */
    public function canManageEvents(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Gibt zurück ob diese Rolle Benutzer verwalten darf.
     */
    public function canManageUsers(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Gibt zurück ob diese Rolle moderieren darf.
     */
    public function canModerate(): bool
    {
        return true; // Both roles can moderate
    }

    /**
     * Gibt zurück ob diese Rolle Daten exportieren darf.
     */
    public function canExport(): bool
    {
        return true; // Both roles can export
    }

    /**
     * Liefert das menschenlesbare Label dieser Rolle.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
        };
    }
}