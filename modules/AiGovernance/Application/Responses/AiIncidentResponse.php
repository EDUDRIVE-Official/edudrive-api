<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Entities\AiIncident;

final readonly class AiIncidentResponse
{
    public function __construct(
        public string $id,
        public string $aiSystemId,
        public string $severity,
        public string $description,
        public string $status,
        public ?string $correctiveActions,
        public string $discoveredAt,
        public ?string $resolvedAt,
    ) {}

    public static function fromIncident(AiIncident $incident): self
    {
        return new self(
            id: $incident->id(),
            aiSystemId: $incident->aiSystemId(),
            severity: $incident->severity()->value,
            description: $incident->description(),
            status: $incident->status()->value,
            correctiveActions: $incident->correctiveActions(),
            discoveredAt: $incident->discoveredAt()->format(DateTimeInterface::ATOM),
            resolvedAt: $incident->resolvedAt()?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ai_system_id' => $this->aiSystemId,
            'severity' => $this->severity,
            'description' => $this->description,
            'status' => $this->status,
            'corrective_actions' => $this->correctiveActions,
            'discovered_at' => $this->discoveredAt,
            'resolved_at' => $this->resolvedAt,
        ];
    }
}
