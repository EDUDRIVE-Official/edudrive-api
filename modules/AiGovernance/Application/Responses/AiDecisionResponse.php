<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Entities\AiDecision;

final readonly class AiDecisionResponse
{
    public function __construct(
        public string $id,
        public string $aiSystemId,
        public ?string $requestedByUserId,
        public string $inputSummary,
        public string $outputSummary,
        public ?float $confidenceLevel,
        public ?int $tokensInput,
        public ?int $tokensOutput,
        public ?float $costAmount,
        public ?int $latencyMs,
        public string $reviewStatus,
        public ?string $reviewedByUserId,
        public ?string $reviewedAt,
        public string $occurredAt,
    ) {}

    public static function fromDecision(AiDecision $decision): self
    {
        return new self(
            id: $decision->id(),
            aiSystemId: $decision->aiSystemId(),
            requestedByUserId: $decision->requestedByUserId(),
            inputSummary: $decision->inputSummary(),
            outputSummary: $decision->outputSummary(),
            confidenceLevel: $decision->confidenceLevel(),
            tokensInput: $decision->tokensInput(),
            tokensOutput: $decision->tokensOutput(),
            costAmount: $decision->costAmount(),
            latencyMs: $decision->latencyMs(),
            reviewStatus: $decision->reviewStatus()->value,
            reviewedByUserId: $decision->reviewedByUserId(),
            reviewedAt: $decision->reviewedAt()?->format(DateTimeInterface::ATOM),
            occurredAt: $decision->occurredAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ai_system_id' => $this->aiSystemId,
            'requested_by_user_id' => $this->requestedByUserId,
            'input_summary' => $this->inputSummary,
            'output_summary' => $this->outputSummary,
            'confidence_level' => $this->confidenceLevel,
            'tokens_input' => $this->tokensInput,
            'tokens_output' => $this->tokensOutput,
            'cost_amount' => $this->costAmount,
            'latency_ms' => $this->latencyMs,
            'review_status' => $this->reviewStatus,
            'reviewed_by_user_id' => $this->reviewedByUserId,
            'reviewed_at' => $this->reviewedAt,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
