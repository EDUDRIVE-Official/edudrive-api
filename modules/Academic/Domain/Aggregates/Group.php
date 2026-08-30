<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Academic\Domain\Exceptions\InvalidGroupPeriod;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;

final class Group
{
    private function __construct(
        private readonly GroupId $id,
        private readonly CourseId $courseId,
        private readonly ?string $organizationId,
        private string $name,
        private ?string $teacherId,
        private readonly DateTimeImmutable $startsAt,
        private readonly DateTimeImmutable $endsAt,
    ) {}

    public static function create(
        GroupId $id,
        CourseId $courseId,
        ?string $organizationId,
        string $name,
        ?string $teacherId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
    ): self {
        return new self(
            id: $id,
            courseId: $courseId,
            organizationId: $organizationId,
            name: self::requireName($name),
            teacherId: $teacherId,
            startsAt: $startsAt,
            endsAt: self::requireValidPeriod($startsAt, $endsAt),
        );
    }

    public static function restore(
        GroupId $id,
        CourseId $courseId,
        ?string $organizationId,
        string $name,
        ?string $teacherId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
    ): self {
        return new self(
            id: $id,
            courseId: $courseId,
            organizationId: $organizationId,
            name: $name,
            teacherId: $teacherId,
            startsAt: $startsAt,
            endsAt: $endsAt,
        );
    }

    public function assignTeacher(?string $teacherId): void
    {
        $this->teacherId = $teacherId;
    }

    public function id(): GroupId
    {
        return $this->id;
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function teacherId(): ?string
    {
        return $this->teacherId;
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    private static function requireName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('El nombre del grupo no puede estar vacío.');
        }

        return $name;
    }

    private static function requireValidPeriod(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): DateTimeImmutable
    {
        if ($endsAt <= $startsAt) {
            throw InvalidGroupPeriod::create();
        }

        return $endsAt;
    }
}
