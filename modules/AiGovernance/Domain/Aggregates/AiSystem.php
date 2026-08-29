<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Aggregates;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresCommitteeApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresExtraordinaryApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresHumanSupervisionForMinors;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiSystemTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final class AiSystem
{
    /** @param list<AiDataCategory> $dataCategories */
    private function __construct(
        private AiSystemId $id,
        private string $name,
        private string $purpose,
        private string $functionalOwnerId,
        private ?string $technicalOwnerId,
        private AiRiskLevel $riskLevel,
        private AiSupervisionLevel $supervisionLevel,
        private array $dataCategories,
        private AiSystemStatus $status,
        private bool $extraordinaryApprovalGranted,
        private ?DateTimeImmutable $extraordinaryApprovalAt,
        private bool $committeeApproved,
        private ?DateTimeImmutable $committeeApprovedAt,
        private ?string $providerEvaluationId,
        private DateTimeImmutable $registeredAt,
    ) {}

    /** @param list<AiDataCategory> $dataCategories */
    public static function register(
        AiSystemId $id,
        string $name,
        string $purpose,
        string $functionalOwnerId,
        ?string $technicalOwnerId,
        AiRiskLevel $riskLevel,
        AiSupervisionLevel $supervisionLevel,
        array $dataCategories,
        ?string $providerEvaluationId = null,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        return new self(
            $id,
            $name,
            $purpose,
            $functionalOwnerId,
            $technicalOwnerId,
            $riskLevel,
            $supervisionLevel,
            $dataCategories,
            AiSystemStatus::Evaluation,
            false,
            null,
            false,
            null,
            $providerEvaluationId,
            $registeredAt ?? new DateTimeImmutable('now'),
        );
    }

    /** @param list<AiDataCategory> $dataCategories */
    public static function restore(
        AiSystemId $id,
        string $name,
        string $purpose,
        string $functionalOwnerId,
        ?string $technicalOwnerId,
        AiRiskLevel $riskLevel,
        AiSupervisionLevel $supervisionLevel,
        array $dataCategories,
        AiSystemStatus $status,
        bool $extraordinaryApprovalGranted,
        ?DateTimeImmutable $extraordinaryApprovalAt,
        bool $committeeApproved,
        ?DateTimeImmutable $committeeApprovedAt,
        ?string $providerEvaluationId,
        DateTimeImmutable $registeredAt,
    ): self {
        return new self(
            $id,
            $name,
            $purpose,
            $functionalOwnerId,
            $technicalOwnerId,
            $riskLevel,
            $supervisionLevel,
            $dataCategories,
            $status,
            $extraordinaryApprovalGranted,
            $extraordinaryApprovalAt,
            $committeeApproved,
            $committeeApprovedAt,
            $providerEvaluationId,
            $registeredAt,
        );
    }

    public function promoteTo(AiSystemStatus $to, DateTimeImmutable $at): void
    {
        if (! in_array($to, $this->allowedTransitions(), true)) {
            throw InvalidAiSystemTransition::create();
        }

        if ($to === AiSystemStatus::Production) {
            $this->assertReadyForProduction();
        }

        $this->status = $to;
    }

    public function grantExtraordinaryApproval(DateTimeImmutable $at): void
    {
        $this->extraordinaryApprovalGranted = true;
        $this->extraordinaryApprovalAt = $at;
    }

    public function approveByCommittee(DateTimeImmutable $at): void
    {
        $this->committeeApproved = true;
        $this->committeeApprovedAt = $at;
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [AiSystemStatus::Production, AiSystemStatus::Pilot], true);
    }

    public function id(): AiSystemId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function functionalOwnerId(): string
    {
        return $this->functionalOwnerId;
    }

    public function technicalOwnerId(): ?string
    {
        return $this->technicalOwnerId;
    }

    public function riskLevel(): AiRiskLevel
    {
        return $this->riskLevel;
    }

    public function supervisionLevel(): AiSupervisionLevel
    {
        return $this->supervisionLevel;
    }

    /** @return list<AiDataCategory> */
    public function dataCategories(): array
    {
        return $this->dataCategories;
    }

    public function status(): AiSystemStatus
    {
        return $this->status;
    }

    public function extraordinaryApprovalGranted(): bool
    {
        return $this->extraordinaryApprovalGranted;
    }

    public function extraordinaryApprovalAt(): ?DateTimeImmutable
    {
        return $this->extraordinaryApprovalAt;
    }

    public function committeeApproved(): bool
    {
        return $this->committeeApproved;
    }

    public function committeeApprovedAt(): ?DateTimeImmutable
    {
        return $this->committeeApprovedAt;
    }

    public function providerEvaluationId(): ?string
    {
        return $this->providerEvaluationId;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    private function assertReadyForProduction(): void
    {
        if ($this->riskLevel === AiRiskLevel::Ia4 && ! $this->extraordinaryApprovalGranted) {
            throw AiSystemRequiresExtraordinaryApproval::create();
        }

        if (in_array($this->riskLevel, [AiRiskLevel::Ia3, AiRiskLevel::Ia4], true) && ! $this->committeeApproved) {
            throw AiSystemRequiresCommitteeApproval::create();
        }

        if (in_array(AiDataCategory::Minors, $this->dataCategories, true)
            && $this->supervisionLevel->value < AiSupervisionLevel::Proposes->value
        ) {
            throw AiSystemRequiresHumanSupervisionForMinors::create();
        }
    }

    /** @return list<AiSystemStatus> */
    private function allowedTransitions(): array
    {
        return match ($this->status) {
            AiSystemStatus::Evaluation => [AiSystemStatus::Pilot, AiSystemStatus::Retired],
            AiSystemStatus::Pilot => [AiSystemStatus::Production, AiSystemStatus::Retired],
            AiSystemStatus::Production => [AiSystemStatus::Suspended, AiSystemStatus::Retired],
            AiSystemStatus::Suspended => [AiSystemStatus::Production, AiSystemStatus::Retired],
            AiSystemStatus::Retired => [],
        };
    }
}
