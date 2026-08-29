<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiIncidentNotFound;
use Modules\AiGovernance\Application\Queries\GetAiIncidentQuery;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;

final readonly class GetAiIncidentHandler
{
    public function __construct(private AiIncidentRepository $incidents) {}

    public function handle(GetAiIncidentQuery $query): AiIncidentResponse
    {
        $incident = $this->incidents->findById($query->incidentId);
        if ($incident === null) {
            throw AiIncidentNotFound::withId($query->incidentId);
        }

        return AiIncidentResponse::fromIncident($incident);
    }
}
