<?php

declare(strict_types=1);

namespace App\Domain\Participation;

enum PostStatus: string
{
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

    /**
     * Prüft ob der Post-Status öffentlich sichtbare Posts zulässt.
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Prüft ob der Post-Status moderierbar ist.
     */
    public function canBeModerated(): bool
    {
        return in_array($this, [self::SUBMITTED, self::APPROVED]);
    }
}