<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Queries\ListAiDecisionsQuery;
use Modules\AiGovernance\Application\Responses\AiDecisionResponse;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class ListAiDecisionsHandler
{
    public function __construct(
        private AiSystemRepository $systems,
        private AiDecisionRepository $decisions,
    ) {}

    /** @return list<AiDecisionResponse> */
    public function handle(ListAiDecisionsQuery $query): array
    {
        $aiSystemId = AiSystemId::fromString($query->aiSystemId);
        if ($this->systems->findById($aiSystemId) === null) {
            throw AiSystemNotFound::withId($query->aiSystemId);
        }

        return array_map(
            static fn (AiDecision $decision): AiDecisionResponse => AiDecisionResponse::fromDecision($decision),
            $this->decisions->findByAiSystem($aiSystemId),
        );
    }
}
