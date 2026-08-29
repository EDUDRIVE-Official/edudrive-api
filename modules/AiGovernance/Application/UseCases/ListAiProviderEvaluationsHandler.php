<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Queries\ListAiProviderEvaluationsQuery;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;

final readonly class ListAiProviderEvaluationsHandler
{
    public function __construct(private AiProviderEvaluationRepository $evaluations) {}

    /** @return list<AiProviderEvaluationResponse> */
    public function handle(ListAiProviderEvaluationsQuery $query): array
    {
        return array_map(
            static fn (AiProviderEvaluation $evaluation): AiProviderEvaluationResponse => AiProviderEvaluationResponse::fromEvaluation($evaluation),
            $this->evaluations->all(),
        );
    }
}
