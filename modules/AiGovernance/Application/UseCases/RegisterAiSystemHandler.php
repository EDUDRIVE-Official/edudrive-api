<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\RegisterAiSystemCommand;
use Modules\AiGovernance\Application\Exceptions\AiProviderEvaluationNotFound;
use Modules\AiGovernance\Application\Exceptions\InvalidAiDataCategory;
use Modules\AiGovernance\Application\Exceptions\InvalidAiRiskLevel;
use Modules\AiGovernance\Application\Exceptions\InvalidAiSupervisionLevel;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class RegisterAiSystemHandler
{
    public function __construct(
        private AiSystemRepository $systems,
        private AiProviderEvaluationRepository $providerEvaluations,
    ) {}

    public function handle(RegisterAiSystemCommand $command): AiSystemResponse
    {
        $riskLevel = AiRiskLevel::tryFrom($command->riskLevel);
        if ($riskLevel === null) {
            throw InvalidAiRiskLevel::withValue($command->riskLevel);
        }

        $supervisionLevel = AiSupervisionLevel::tryFrom($command->supervisionLevel);
        if ($supervisionLevel === null) {
            throw InvalidAiSupervisionLevel::withValue($command->supervisionLevel);
        }

        $dataCategories = array_map(static function (string $category): AiDataCategory {
            $value = AiDataCategory::tryFrom($category);
            if ($value === null) {
                throw InvalidAiDataCategory::withValue($category);
            }

            return $value;
        }, $command->dataCategories);

        if ($command->providerEvaluationId !== null
            && $this->providerEvaluations->findById(AiProviderEvaluationId::fromString($command->providerEvaluationId)) === null
        ) {
            throw AiProviderEvaluationNotFound::withId($command->providerEvaluationId);
        }

        $system = AiSystem::register(
            id: AiSystemId::fromString((string) Str::uuid()),
            name: $command->name,
            purpose: $command->purpose,
            functionalOwnerId: $command->functionalOwnerId,
            technicalOwnerId: $command->technicalOwnerId,
            riskLevel: $riskLevel,
            supervisionLevel: $supervisionLevel,
            dataCategories: $dataCategories,
            providerEvaluationId: $command->providerEvaluationId,
        );

        $this->systems->save($system);

        return AiSystemResponse::fromSystem($system);
    }
}
