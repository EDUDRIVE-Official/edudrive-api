<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use DateTimeInterface;
use Modules\Gamification\Domain\Aggregates\Badge;

final readonly class BadgeResponse
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $description,
        public string $criteria,
        public string $category,
        public string $level,
        public int $version,
        public string $status,
        public string $registeredAt,
        public ?string $retiredAt,
        public ?string $retiredReason,
    ) {}

    public static function fromBadge(Badge $badge): self
    {
        return new self(
            id: $badge->id()->value(),
            code: $badge->code()->value(),
            name: $badge->name(),
            description: $badge->description(),
            criteria: $badge->criteria(),
            category: $badge->category()->value,
            level: $badge->level()->value,
            version: $badge->version(),
            status: $badge->status()->value,
            registeredAt: $badge->registeredAt()->format(DateTimeInterface::ATOM),
            retiredAt: $badge->retiredAt()?->format(DateTimeInterface::ATOM),
            retiredReason: $badge->retiredReason(),
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
            'criteria' => $this->criteria,
            'category' => $this->category,
            'level' => $this->level,
            'version' => $this->version,
            'status' => $this->status,
            'registered_at' => $this->registeredAt,
            'retired_at' => $this->retiredAt,
            'retired_reason' => $this->retiredReason,
        ];
    }
}
