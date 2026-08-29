<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;

final readonly class AiSystemResponse
{
    /** @param list<string> $dataCategories */
    public function __construct(
        public string $id,
        public string $name,
        public string $purpose,
        public string $functionalOwnerId,
        public ?string $technicalOwnerId,
        public string $riskLevel,
        public int $supervisionLevel,
        public array $dataCategories,
        public string $status,
        public bool $extraordinaryApprovalGranted,
        public bool $committeeApproved,
        public ?string $providerEvaluationId,
        public string $registeredAt,
    ) {}

    public static function fromSystem(AiSystem $system): self
    {
        return new self(
            id: $system->id()->value(),
            name: $system->name(),
            purpose: $system->purpose(),
            functionalOwnerId: $system->functionalOwnerId(),
            technicalOwnerId: $system->technicalOwnerId(),
            riskLevel: $system->riskLevel()->value,
            supervisionLevel: $system->supervisionLevel()->value,
            dataCategories: array_map(static fn (AiDataCategory $category): string => $category->value, $system->dataCategories()),
            status: $system->status()->value,
            extraordinaryApprovalGranted: $system->extraordinaryApprovalGranted(),
            committeeApproved: $system->committeeApproved(),
            providerEvaluationId: $system->providerEvaluationId(),
            registeredAt: $system->registeredAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'purpose' => $this->purpose,
            'functional_owner_id' => $this->functionalOwnerId,
            'technical_owner_id' => $this->technicalOwnerId,
            'risk_level' => $this->riskLevel,
            'supervision_level' => $this->supervisionLevel,
            'data_categories' => $this->dataCategories,
            'status' => $this->status,
            'extraordinary_approval_granted' => $this->extraordinaryApprovalGranted,
            'committee_approved' => $this->committeeApproved,
            'provider_evaluation_id' => $this->providerEvaluationId,
            'registered_at' => $this->registeredAt,
        ];
    }
}
