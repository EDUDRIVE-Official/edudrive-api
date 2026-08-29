<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Entities;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiDecisionReviewStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiDecisionReview;

final class AiDecision
{
    private function __construct(
        private string $id,
        private string $aiSystemId,
        private ?string $requestedByUserId,
        private string $inputSummary,
        private string $outputSummary,
        private ?float $confidenceLevel,
        private ?int $tokensInput,
        private ?int $tokensOutput,
        private ?float $costAmount,
        private ?int $latencyMs,
        private AiDecisionReviewStatus $reviewStatus,
        private ?string $reviewedByUserId,
        private ?DateTimeImmutable $reviewedAt,
        private DateTimeImmutable $occurredAt,
    ) {}

    public static function record(
        string $id,
        string $aiSystemId,
        ?string $requestedByUserId,
        string $inputSummary,
        string $outputSummary,
        bool $requiresReview,
        ?float $confidenceLevel = null,
        ?int $tokensInput = null,
        ?int $tokensOutput = null,
        ?float $costAmount = null,
        ?int $latencyMs = null,
        ?DateTimeImmutable $occurredAt = null,
    ): self {
        return new self(
            $id,
            $aiSystemId,
            $requestedByUserId,
            $inputSummary,
            $outputSummary,
            $confidenceLevel,
            $tokensInput,
            $tokensOutput,
            $costAmount,
            $latencyMs,
            $requiresReview ? AiDecisionReviewStatus::Pending : AiDecisionReviewStatus::NotRequired,
            null,
            null,
            $occurredAt ?? new DateTimeImmutable('now'),
        );
    }

    public static function restore(
        string $id,
        string $aiSystemId,
        ?string $requestedByUserId,
        string $inputSummary,
        string $outputSummary,
        ?float $confidenceLevel,
        ?int $tokensInput,
        ?int $tokensOutput,
        ?float $costAmount,
        ?int $latencyMs,
        AiDecisionReviewStatus $reviewStatus,
        ?string $reviewedByUserId,
        ?DateTimeImmutable $reviewedAt,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            $aiSystemId,
            $requestedByUserId,
            $inputSummary,
            $outputSummary,
            $confidenceLevel,
            $tokensInput,
            $tokensOutput,
            $costAmount,
            $latencyMs,
            $reviewStatus,
            $reviewedByUserId,
            $reviewedAt,
            $occurredAt,
        );
    }

    public function approve(string $reviewerUserId, DateTimeImmutable $at): void
    {
        if ($this->reviewStatus !== AiDecisionReviewStatus::Pending) {
            throw InvalidAiDecisionReview::create();
        }

        $this->reviewStatus = AiDecisionReviewStatus::Approved;
        $this->reviewedByUserId = $reviewerUserId;
        $this->reviewedAt = $at;
    }

    public function reject(string $reviewerUserId, DateTimeImmutable $at): void
    {
        if ($this->reviewStatus !== AiDecisionReviewStatus::Pending) {
            throw InvalidAiDecisionReview::create();
        }

        $this->reviewStatus = AiDecisionReviewStatus::Rejected;
        $this->reviewedByUserId = $reviewerUserId;
        $this->reviewedAt = $at;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function aiSystemId(): string
    {
        return $this->aiSystemId;
    }

    public function requestedByUserId(): ?string
    {
        return $this->requestedByUserId;
    }

    public function inputSummary(): string
    {
        return $this->inputSummary;
    }

    public function outputSummary(): string
    {
        return $this->outputSummary;
    }

    public function confidenceLevel(): ?float
    {
        return $this->confidenceLevel;
    }

    public function tokensInput(): ?int
    {
        return $this->tokensInput;
    }

    public function tokensOutput(): ?int
    {
        return $this->tokensOutput;
    }

    public function costAmount(): ?float
    {
        return $this->costAmount;
    }

    public function latencyMs(): ?int
    {
        return $this->latencyMs;
    }

    public function reviewStatus(): AiDecisionReviewStatus
    {
        return $this->reviewStatus;
    }

    public function reviewedByUserId(): ?string
    {
        return $this->reviewedByUserId;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
