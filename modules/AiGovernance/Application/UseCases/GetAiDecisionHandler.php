<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiDecisionNotFound;
use Modules\AiGovernance\Application\Queries\GetAiDecisionQuery;
use Modules\AiGovernance\Application\Responses\AiDecisionResponse;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;

final readonly class GetAiDecisionHandler
{
    public function __construct(private AiDecisionRepository $decisions) {}

    public function handle(GetAiDecisionQuery $query): AiDecisionResponse
    {
        $decision = $this->decisions->findById($query->decisionId);
        if ($decision === null) {
            throw AiDecisionNotFound::withId($query->decisionId);
        }

        return AiDecisionResponse::fromDecision($decision);
    }
}
