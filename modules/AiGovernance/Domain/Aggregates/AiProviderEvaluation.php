<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Aggregates;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiProviderApprovalStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiProviderEvaluationTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

final class AiProviderEvaluation
{
    private function __construct(
        private AiProviderEvaluationId $id,
        private string $providerName,
        private string $dataLocation,
        private string $retentionPolicy,
        private ?string $securityReviewNotes,
        private AiProviderApprovalStatus $approvalStatus,
        private ?DateTimeImmutable $reviewedAt,
        private ?DateTimeImmutable $nextReviewDueAt,
    ) {}

    public static function register(
        AiProviderEvaluationId $id,
        string $providerName,
        string $dataLocation,
        string $retentionPolicy,
        ?string $securityReviewNotes = null,
    ): self {
        return new self(
            $id,
            $providerName,
            $dataLocation,
            $retentionPolicy,
            $securityReviewNotes,
            AiProviderApprovalStatus::PendingReview,
            null,
            null,
        );
    }

    public static function restore(
        AiProviderEvaluationId $id,
        string $providerName,
        string $dataLocation,
        string $retentionPolicy,
        ?string $securityReviewNotes,
        AiProviderApprovalStatus $approvalStatus,
        ?DateTimeImmutable $reviewedAt,
        ?DateTimeImmutable $nextReviewDueAt,
    ): self {
        return new self($id, $providerName, $dataLocation, $retentionPolicy, $securityReviewNotes, $approvalStatus, $reviewedAt, $nextReviewDueAt);
    }

    public function approve(DateTimeImmutable $at, ?DateTimeImmutable $nextReviewDueAt): void
    {
        if ($this->approvalStatus === AiProviderApprovalStatus::Approved) {
            throw InvalidAiProviderEvaluationTransition::create();
        }

        $this->approvalStatus = AiProviderApprovalStatus::Approved;
        $this->reviewedAt = $at;
        $this->nextReviewDueAt = $nextReviewDueAt;
    }

    public function reject(DateTimeImmutable $at): void
    {
        if ($this->approvalStatus === AiProviderApprovalStatus::Rejected) {
            throw InvalidAiProviderEvaluationTransition::create();
        }

        $this->approvalStatus = AiProviderApprovalStatus::Rejected;
        $this->reviewedAt = $at;
    }

    public function requireReevaluation(DateTimeImmutable $at): void
    {
        $this->approvalStatus = AiProviderApprovalStatus::RequiresReevaluation;
        $this->reviewedAt = $at;
    }

    public function id(): AiProviderEvaluationId
    {
        return $this->id;
    }

    public function providerName(): string
    {
        return $this->providerName;
    }

    public function dataLocation(): string
    {
        return $this->dataLocation;
    }

    public function retentionPolicy(): string
    {
        return $this->retentionPolicy;
    }

    public function securityReviewNotes(): ?string
    {
        return $this->securityReviewNotes;
    }

    public function approvalStatus(): AiProviderApprovalStatus
    {
        return $this->approvalStatus;
    }

    public function reviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function nextReviewDueAt(): ?DateTimeImmutable
    {
        return $this->nextReviewDueAt;
    }
}
