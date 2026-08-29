<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Commands\StartAiIncidentInvestigationCommand;
use Modules\AiGovernance\Application\Exceptions\AiIncidentNotFound;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;

final readonly class StartAiIncidentInvestigationHandler
{
    public function __construct(private AiIncidentRepository $incidents) {}

    public function handle(StartAiIncidentInvestigationCommand $command): AiIncidentResponse
    {
        $incident = $this->incidents->findById($command->incidentId);
        if ($incident === null) {
            throw AiIncidentNotFound::withId($command->incidentId);
        }

        $incident->startInvestigation();
        $this->incidents->save($incident);

        return AiIncidentResponse::fromIncident($incident);
    }
}
