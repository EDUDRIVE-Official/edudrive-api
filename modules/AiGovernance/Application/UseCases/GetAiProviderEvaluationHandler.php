<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiProviderEvaluationNotFound;
use Modules\AiGovernance\Application\Queries\GetAiProviderEvaluationQuery;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

final readonly class GetAiProviderEvaluationHandler
{
    public function __construct(private AiProviderEvaluationRepository $evaluations) {}

    public function handle(GetAiProviderEvaluationQuery $query): AiProviderEvaluationResponse
    {
        $evaluation = $this->evaluations->findById(AiProviderEvaluationId::fromString($query->providerEvaluationId));
        if ($evaluation === null) {
            throw AiProviderEvaluationNotFound::withId($query->providerEvaluationId);
        }

        return AiProviderEvaluationResponse::fromEvaluation($evaluation);
    }
}
