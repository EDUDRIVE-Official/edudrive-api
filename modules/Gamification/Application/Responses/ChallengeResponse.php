<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Aggregates\Challenge;

final readonly class ChallengeResponse
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $description,
        public string $type,
        public string $reward,
        public string $startsAt,
        public string $endsAt,
        public string $status,
        public string $registeredAt,
        public ?string $retiredAt,
        public ?string $retiredReason,
    ) {}

    public static function fromChallenge(Challenge $challenge): self
    {
        return new self(
            id: $challenge->id()->value(),
            code: $challenge->code()->value(),
            name: $challenge->name(),
            description: $challenge->description(),
            type: $challenge->type()->value,
            reward: $challenge->reward(),
            startsAt: $challenge->startsAt()->format(DateTimeInterface::ATOM),
            endsAt: $challenge->endsAt()->format(DateTimeInterface::ATOM),
            status: $challenge->status()->value,
            registeredAt: $challenge->registeredAt()->format(DateTimeInterface::ATOM),
            retiredAt: $challenge->retiredAt()?->format(DateTimeInterface::ATOM),
            retiredReason: $challenge->retiredReason(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'reward' => $this->reward,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'status' => $this->status,
            'registered_at' => $this->registeredAt,
            'retired_at' => $this->retiredAt,
            'retired_reason' => $this->retiredReason,
        ];
    }
}
