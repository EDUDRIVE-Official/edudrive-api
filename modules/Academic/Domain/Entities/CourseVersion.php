<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Academic\Domain\Enums\CourseVersionStatus;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class CourseVersion
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function __construct(
        private string $id,
        private CourseId $courseId,
        private int $versionNumber,
        private CourseVersionStatus $status,
        private array $snapshot,
        private DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function create(
        string $id,
        CourseId $courseId,
        int $versionNumber,
        array $snapshot,
        DateTimeImmutable $publishedAt,
    ): self {
        return new self(
            id: $id,
            courseId: $courseId,
            versionNumber: self::normalizeVersionNumber($versionNumber),
            status: CourseVersionStatus::Published,
            snapshot: $snapshot,
            publishedAt: $publishedAt,
            archivedAt: null,
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function restore(
        string $id,
        CourseId $courseId,
        int $versionNumber,
        CourseVersionStatus $status,
        array $snapshot,
        DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt = null,
    ): self {
        return new self(
            id: $id,
            courseId: $courseId,
            versionNumber: self::normalizeVersionNumber($versionNumber),
            status: $status,
            snapshot: $snapshot,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function status(): CourseVersionStatus
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->snapshot;
    }

    public function publishedAt(): DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    private static function normalizeVersionNumber(int $versionNumber): int
    {
        if ($versionNumber < 1) {
            throw new InvalidArgumentException('El numero de version debe iniciar en uno.');
        }

        return $versionNumber;
    }
}
