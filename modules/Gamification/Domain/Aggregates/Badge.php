<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Enums\BadgeStatus;
use Modules\Gamification\Domain\Exceptions\InvalidBadgeTransition;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final class Badge
{
    private function __construct(
        private BadgeId $id,
        private BadgeCode $code,
        private string $name,
        private string $description,
        private string $criteria,
        private BadgeCategory $category,
        private BadgeLevel $level,
        private int $version,
        private BadgeStatus $status,
        private DateTimeImmutable $registeredAt,
        private ?DateTimeImmutable $retiredAt,
        private ?string $retiredReason,
    ) {}

    public static function create(
        BadgeId $id,
        BadgeCode $code,
        string $name,
        string $description,
        string $criteria,
        BadgeCategory $category,
        BadgeLevel $level,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            $id,
            $code,
            $name,
            $description,
            $criteria,
            $category,
            $level,
            1,
            BadgeStatus::Active,
            $registeredAt ?? new DateTimeImmutable('now'),
            null,
            null,
        );
    }

    public static function restore(
        BadgeId $id,
        BadgeCode $code,
        string $name,
        string $description,
        string $criteria,
        BadgeCategory $category,
        BadgeLevel $level,
        int $version,
        BadgeStatus $status,
        DateTimeImmutable $registeredAt,
        ?DateTimeImmutable $retiredAt,
        ?string $retiredReason,
    ): self {
        return new self($id, $code, $name, $description, $criteria, $category, $level, $version, $status, $registeredAt, $retiredAt, $retiredReason);
    }

    public function updateContent(
        string $name,
        string $description,
        string $criteria,
        BadgeCategory $category,
        BadgeLevel $level,
    ): void {
        if ($this->status === BadgeStatus::Retired) {
            throw InvalidBadgeTransition::cannotEditRetired();
        }

        $this->name = $name;
        $this->description = $description;
        $this->criteria = $criteria;
        $this->category = $category;
        $this->level = $level;
        $this->version++;
    }

    public function retire(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === BadgeStatus::Retired) {
            throw InvalidBadgeTransition::alreadyRetired();
        }

        $this->status = BadgeStatus::Retired;
        $this->retiredAt = $at;
        $this->retiredReason = $reason;
    }

    public function id(): BadgeId
    {
        return $this->id;
    }

    public function code(): BadgeCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function criteria(): string
    {
        return $this->criteria;
    }

    public function category(): BadgeCategory
    {
        return $this->category;
    }

    public function level(): BadgeLevel
    {
        return $this->level;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function status(): BadgeStatus
    {
        return $this->status;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function retiredAt(): ?DateTimeImmutable
    {
        return $this->retiredAt;
    }

    public function retiredReason(): ?string
    {
        return $this->retiredReason;
    }
}
