<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use DateTimeImmutable;
use Modules\AiGovernance\Application\Commands\RejectAiDecisionCommand;
use Modules\AiGovernance\Application\Exceptions\AiDecisionNotFound;
use Modules\AiGovernance\Application\Responses\AiDecisionResponse;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;

final readonly class RejectAiDecisionHandler
{
    public function __construct(private AiDecisionRepository $decisions) {}

    public function handle(RejectAiDecisionCommand $command): AiDecisionResponse
    {
        $decision = $this->decisions->findById($command->decisionId);
        if ($decision === null) {
            throw AiDecisionNotFound::withId($command->decisionId);
        }

        $decision->reject($command->reviewerUserId, new DateTimeImmutable('now'));
        $this->decisions->save($decision);

        return AiDecisionResponse::fromDecision($decision);
    }
}
