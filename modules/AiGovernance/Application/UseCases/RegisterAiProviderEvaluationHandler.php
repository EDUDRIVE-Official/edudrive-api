<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\RegisterAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

final readonly class RegisterAiProviderEvaluationHandler
{
    public function __construct(private AiProviderEvaluationRepository $evaluations) {}

    public function handle(RegisterAiProviderEvaluationCommand $command): AiProviderEvaluationResponse
    {
        $evaluation = AiProviderEvaluation::register(
            id: AiProviderEvaluationId::fromString((string) Str::uuid()),
            providerName: $command->providerName,
            dataLocation: $command->dataLocation,
            retentionPolicy: $command->retentionPolicy,
            securityReviewNotes: $command->securityReviewNotes,
        );

        $this->evaluations->save($evaluation);

        return AiProviderEvaluationResponse::fromEvaluation($evaluation);
    }
}
