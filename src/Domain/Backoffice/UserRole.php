<?php

declare(strict_types=1);

namespace App\Domain\Backoffice;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case MODERATOR = 'ROLE_MODERATOR';

    public function canManageEvents(): bool
    {
        return $this === self::ADMIN;
    }

    public function canManageUsers(): bool
    {
        return $this === self::ADMIN;
    }

    public function canModerate(): bool
    {
        return true; // Both roles can moderate
    }

    public function canExport(): bool
    {
        return true; // Both roles can export
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MODERATOR => 'Moderator',
        };
    }
}