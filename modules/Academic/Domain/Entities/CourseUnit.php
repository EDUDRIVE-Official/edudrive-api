<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumDuration;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumText;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;

final readonly class CourseUnit
{
    /** @param list<CourseUnitId> $prerequisiteUnitIds */
    private function __construct(
        private CourseUnitId $id,
        private CurriculumCode $code,
        private string $title,
        private string $description,
        private ?string $objectives,
        private ?int $durationMinutes,
        private int $position,
        private array $prerequisiteUnitIds,
    ) {}

    /** @param list<CourseUnitId> $prerequisiteUnitIds */
    public static function create(
        CourseUnitId $id,
        CurriculumCode $code,
        string $title,
        string $description,
        ?string $objectives,
        ?int $durationMinutes,
        int $position,
        array $prerequisiteUnitIds,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: self::normalizeTitle($title),
            description: self::normalizeDescription($description),
            objectives: self::normalizeObjectives($objectives),
            durationMinutes: self::normalizeDuration($durationMinutes),
            position: self::normalizePosition($position),
            prerequisiteUnitIds: $prerequisiteUnitIds,
        );
    }

    public function id(): CourseUnitId
    {
        return $this->id;
    }

    public function code(): CurriculumCode
    {
        return $this->code;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function objectives(): ?string
    {
        return $this->objectives;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function position(): int
    {
        return $this->position;
    }

    /** @return list<CourseUnitId> */
    public function prerequisiteUnitIds(): array
    {
        return $this->prerequisiteUnitIds;
    }

    private static function normalizeTitle(string $title): string
    {
        $title = trim($title);

        if ($title === '' || mb_strlen($title) > 180) {
            throw InvalidCurriculumText::forField('titulo');
        }

        return $title;
    }

    private static function normalizeDescription(string $description): string
    {
        $description = trim($description);

        if ($description === '') {
            throw InvalidCurriculumText::forField('descripcion');
        }

        return $description;
    }

    private static function normalizeObjectives(?string $objectives): ?string
    {
        $objectives = $objectives === null ? null : trim($objectives);

        return $objectives === '' ? null : $objectives;
    }

    private static function normalizeDuration(?int $durationMinutes): ?int
    {
        if ($durationMinutes !== null && $durationMinutes < 1) {
            throw InvalidCurriculumDuration::create();
        }

        return $durationMinutes;
    }

    private static function normalizePosition(int $position): int
    {
        if ($position < 1) {
            throw new InvalidArgumentException('La posicion de la unidad debe iniciar en uno.');
        }

        return $position;
    }
}
