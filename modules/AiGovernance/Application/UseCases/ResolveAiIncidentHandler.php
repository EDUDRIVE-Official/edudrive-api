<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use DateTimeImmutable;
use Modules\AiGovernance\Application\Commands\ResolveAiIncidentCommand;
use Modules\AiGovernance\Application\Exceptions\AiIncidentNotFound;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;

final readonly class ResolveAiIncidentHandler
{
    public function __construct(private AiIncidentRepository $incidents) {}

    public function handle(ResolveAiIncidentCommand $command): AiIncidentResponse
    {
        $incident = $this->incidents->findById($command->incidentId);
        if ($incident === null) {
            throw AiIncidentNotFound::withId($command->incidentId);
        }

        $incident->resolve($command->correctiveActions, new DateTimeImmutable('now'));
        $this->incidents->save($incident);

        return AiIncidentResponse::fromIncident($incident);
    }
}
