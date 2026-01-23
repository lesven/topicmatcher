<?php

declare(strict_types=1);

namespace App\Domain\EventManagement;

/**
 * Enum representing the possible statuses of an Event and helper methods
 * to determine permissions and display attributes for each status.
 */
enum EventStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    /**
     * Prüft ob dieses Event-Status öffentlich sichtbar ist.
     */
    public function isPubliclyVisible(): bool
    {
        return $this !== self::ARCHIVED;
    }

    /**
     * Prüft ob Einreichungen bei diesem Status erlaubt sind.
     */
    public function allowsSubmissions(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Prüft ob neue Posts in diesem Status angelegt werden dürfen.
     */
    public function allowsNewPosts(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Prüft ob Interessenbekundungen in diesem Status erlaubt sind.
     */
    public function allowsInterests(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Prüft ob Moderation in diesem Status erlaubt ist.
     */
    public function allowsModeration(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Prüft ob ein Export in diesem Status sinnvoll/erlaubt ist.
     */
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