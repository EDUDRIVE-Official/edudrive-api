<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Aggregates\Achievement;

final readonly class AchievementResponse
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $description,
        public string $earningRule,
        public string $status,
        public string $registeredAt,
        public ?string $retiredAt,
        public ?string $retiredReason,
    ) {}

    public static function fromAchievement(Achievement $achievement): self
    {
        return new self(
            id: $achievement->id()->value(),
            code: $achievement->code()->value(),
            name: $achievement->name(),
            description: $achievement->description(),
            earningRule: $achievement->earningRule(),
            status: $achievement->status()->value,
            registeredAt: $achievement->registeredAt()->format(DateTimeInterface::ATOM),
            retiredAt: $achievement->retiredAt()?->format(DateTimeInterface::ATOM),
            retiredReason: $achievement->retiredReason(),
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
            'earning_rule' => $this->earningRule,
            'status' => $this->status,
            'registered_at' => $this->registeredAt,
            'retired_at' => $this->retiredAt,
            'retired_reason' => $this->retiredReason,
        ];
    }
}
