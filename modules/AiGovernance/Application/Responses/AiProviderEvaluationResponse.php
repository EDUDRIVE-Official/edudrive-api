<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;

final readonly class AiProviderEvaluationResponse
{
    public function __construct(
        public string $id,
        public string $providerName,
        public string $dataLocation,
        public string $retentionPolicy,
        public ?string $securityReviewNotes,
        public string $approvalStatus,
        public ?string $reviewedAt,
        public ?string $nextReviewDueAt,
    ) {}

    public static function fromEvaluation(AiProviderEvaluation $evaluation): self
    {
        return new self(
            id: $evaluation->id()->value(),
            providerName: $evaluation->providerName(),
            dataLocation: $evaluation->dataLocation(),
            retentionPolicy: $evaluation->retentionPolicy(),
            securityReviewNotes: $evaluation->securityReviewNotes(),
            approvalStatus: $evaluation->approvalStatus()->value,
            reviewedAt: $evaluation->reviewedAt()?->format(DateTimeInterface::ATOM),
            nextReviewDueAt: $evaluation->nextReviewDueAt()?->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'provider_name' => $this->providerName,
            'data_location' => $this->dataLocation,
            'retention_policy' => $this->retentionPolicy,
            'security_review_notes' => $this->securityReviewNotes,
            'approval_status' => $this->approvalStatus,
            'reviewed_at' => $this->reviewedAt,
            'next_review_due_at' => $this->nextReviewDueAt,
        ];
    }
}
