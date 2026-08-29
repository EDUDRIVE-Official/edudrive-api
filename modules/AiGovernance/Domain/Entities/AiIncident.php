<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Entities;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Enums\AiIncidentStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiIncidentTransition;

final class AiIncident
{
    private function __construct(
        private string $id,
        private string $aiSystemId,
        private AiIncidentSeverity $severity,
        private string $description,
        private AiIncidentStatus $status,
        private ?string $correctiveActions,
        private DateTimeImmutable $discoveredAt,
        private ?DateTimeImmutable $resolvedAt,
    ) {}

    public static function report(
        string $id,
        string $aiSystemId,
        AiIncidentSeverity $severity,
        string $description,
        ?DateTimeImmutable $discoveredAt = null,
    ): self {
        return new self(
            $id,
            $aiSystemId,
            $severity,
            $description,
            AiIncidentStatus::Open,
            null,
            $discoveredAt ?? new DateTimeImmutable('now'),
            null,
        );
    }

    public static function restore(
        string $id,
        string $aiSystemId,
        AiIncidentSeverity $severity,
        string $description,
        AiIncidentStatus $status,
        ?string $correctiveActions,
        DateTimeImmutable $discoveredAt,
        ?DateTimeImmutable $resolvedAt,
    ): self {
        return new self($id, $aiSystemId, $severity, $description, $status, $correctiveActions, $discoveredAt, $resolvedAt);
    }

    public function startInvestigation(): void
    {
        if ($this->status !== AiIncidentStatus::Open) {
            throw InvalidAiIncidentTransition::create();
        }

        $this->status = AiIncidentStatus::Investigating;
    }

    public function resolve(string $correctiveActions, DateTimeImmutable $at): void
    {
        if ($this->status === AiIncidentStatus::Resolved) {
            throw InvalidAiIncidentTransition::create();
        }

        $this->status = AiIncidentStatus::Resolved;
        $this->correctiveActions = $correctiveActions;
        $this->resolvedAt = $at;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function aiSystemId(): string
    {
        return $this->aiSystemId;
    }

    public function severity(): AiIncidentSeverity
    {
        return $this->severity;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): AiIncidentStatus
    {
        return $this->status;
    }

    public function correctiveActions(): ?string
    {
        return $this->correctiveActions;
    }

    public function discoveredAt(): DateTimeImmutable
    {
        return $this->discoveredAt;
    }

    public function resolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }
}
