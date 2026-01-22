<?php

declare(strict_types=1);

namespace App\Domain\EventManagement;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function isPubliclyVisible(): bool
    {
        return $this !== self::ARCHIVED;
    }

    public function allowsSubmissions(): bool
    {
        return $this === self::ACTIVE;
    }

    public function allowsNewPosts(): bool
    {
        return $this === self::ACTIVE;
    }

    public function allowsInterests(): bool
    {
        return $this === self::ACTIVE;
    }

    public function allowsModeration(): bool
    {
        return $this === self::ACTIVE;
    }

    public function allowsExport(): bool
    {
        return in_array($this, [self::CLOSED, self::ARCHIVED]);
    }

    /**
     * Returns the Bootstrap color class for this status
     */
    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::ACTIVE => 'success', 
            self::CLOSED => 'warning',
            self::ARCHIVED => 'dark',
        };
    }

    /**
     * Returns the order/priority of this status for timeline display
     */
    public function getOrder(): int
    {
        return match($this) {
            self::DRAFT => 0,
            self::ACTIVE => 1,
            self::CLOSED => 2,
            self::ARCHIVED => 3,
        };
    }

    /**
     * Returns the human-readable label for this status
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Entwurf',
            self::ACTIVE => 'Aktiv',
            self::CLOSED => 'Geschlossen',
            self::ARCHIVED => 'Archiviert',
        };
    }
}