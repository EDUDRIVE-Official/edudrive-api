<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isUnderReview(): bool
    {
        return $this === self::UnderReview;
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::UnderReview => 'En revisión',
            self::Approved => 'Aprobado',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }
}
