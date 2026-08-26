<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Gamification\Domain\Enums\AchievementStatus;
use Modules\Gamification\Domain\Exceptions\InvalidAchievementTransition;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final class Achievement
{
    private function __construct(
        private AchievementId $id,
        private AchievementCode $code,
        private string $name,
        private string $description,
        private string $earningRule,
        private AchievementStatus $status,
        private DateTimeImmutable $registeredAt,
        private ?DateTimeImmutable $retiredAt,
        private ?string $retiredReason,
    ) {}

    public static function create(
        AchievementId $id,
        AchievementCode $code,
        string $name,
        string $description,
        string $earningRule,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            $id,
            $code,
            $name,
            $description,
            $earningRule,
            AchievementStatus::Active,
            $registeredAt ?? new DateTimeImmutable('now'),
            null,
            null,
        );
    }

    public static function restore(
        AchievementId $id,
        AchievementCode $code,
        string $name,
        string $description,
        string $earningRule,
        AchievementStatus $status,
        DateTimeImmutable $registeredAt,
        ?DateTimeImmutable $retiredAt,
        ?string $retiredReason,
    ): self {
        return new self($id, $code, $name, $description, $earningRule, $status, $registeredAt, $retiredAt, $retiredReason);
    }

    public function retire(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === AchievementStatus::Retired) {
            throw InvalidAchievementTransition::create();
        }

        $this->status = AchievementStatus::Retired;
        $this->retiredAt = $at;
        $this->retiredReason = $reason;
    }

    public function id(): AchievementId
    {
        return $this->id;
    }

    public function code(): AchievementCode
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

    public function earningRule(): string
    {
        return $this->earningRule;
    }

    public function status(): AchievementStatus
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
