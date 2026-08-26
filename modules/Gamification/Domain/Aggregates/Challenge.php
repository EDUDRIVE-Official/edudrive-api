<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Gamification\Domain\Enums\ChallengeStatus;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Exceptions\InvalidChallengeTransition;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final class Challenge
{
    private function __construct(
        private ChallengeId $id,
        private ChallengeCode $code,
        private string $name,
        private string $description,
        private ChallengeType $type,
        private string $reward,
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt,
        private ChallengeStatus $status,
        private DateTimeImmutable $registeredAt,
        private ?DateTimeImmutable $retiredAt,
        private ?string $retiredReason,
    ) {}

    public static function create(
        ChallengeId $id,
        ChallengeCode $code,
        string $name,
        string $description,
        ChallengeType $type,
        string $reward,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        self::guardDateWindow($startsAt, $endsAt);

        return new self(
            $id,
            $code,
            $name,
            $description,
            $type,
            $reward,
            $startsAt,
            $endsAt,
            ChallengeStatus::Active,
            $registeredAt ?? new DateTimeImmutable('now'),
            null,
            null,
        );
    }

    public static function restore(
        ChallengeId $id,
        ChallengeCode $code,
        string $name,
        string $description,
        ChallengeType $type,
        string $reward,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
        ChallengeStatus $status,
        DateTimeImmutable $registeredAt,
        ?DateTimeImmutable $retiredAt,
        ?string $retiredReason,
    ): self {
        self::guardDateWindow($startsAt, $endsAt);

        return new self($id, $code, $name, $description, $type, $reward, $startsAt, $endsAt, $status, $registeredAt, $retiredAt, $retiredReason);
    }

    public function retire(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === ChallengeStatus::Retired) {
            throw InvalidChallengeTransition::create();
        }

        $this->status = ChallengeStatus::Retired;
        $this->retiredAt = $at;
        $this->retiredReason = $reason;
    }

    public function isWithinWindow(DateTimeImmutable $at): bool
    {
        return $at >= $this->startsAt && $at <= $this->endsAt;
    }

    public function id(): ChallengeId
    {
        return $this->id;
    }

    public function code(): ChallengeCode
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

    public function type(): ChallengeType
    {
        return $this->type;
    }

    public function reward(): string
    {
        return $this->reward;
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function status(): ChallengeStatus
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

    private static function guardDateWindow(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): void
    {
        if ($endsAt <= $startsAt) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
        }
    }
}
