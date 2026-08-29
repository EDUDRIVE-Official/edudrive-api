<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use DateTimeImmutable;
use Modules\AiGovernance\Application\Commands\RequireAiProviderReevaluationCommand;
use Modules\AiGovernance\Application\Exceptions\AiProviderEvaluationNotFound;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

final readonly class RequireAiProviderReevaluationHandler
{
    public function __construct(private AiProviderEvaluationRepository $evaluations) {}

    public function handle(RequireAiProviderReevaluationCommand $command): AiProviderEvaluationResponse
    {
        $evaluation = $this->evaluations->findById(AiProviderEvaluationId::fromString($command->providerEvaluationId));
        if ($evaluation === null) {
            throw AiProviderEvaluationNotFound::withId($command->providerEvaluationId);
        }

        $evaluation->requireReevaluation(new DateTimeImmutable('now'));
        $this->evaluations->save($evaluation);

        return AiProviderEvaluationResponse::fromEvaluation($evaluation);
    }
}
