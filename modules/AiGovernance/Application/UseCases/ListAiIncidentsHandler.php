<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Queries\ListAiIncidentsQuery;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class ListAiIncidentsHandler
{
    public function __construct(
        private AiSystemRepository $systems,
        private AiIncidentRepository $incidents,
    ) {}

    /** @return list<AiIncidentResponse> */
    public function handle(ListAiIncidentsQuery $query): array
    {
        $aiSystemId = AiSystemId::fromString($query->aiSystemId);
        if ($this->systems->findById($aiSystemId) === null) {
            throw AiSystemNotFound::withId($query->aiSystemId);
        }

        return array_map(
            static fn (AiIncident $incident): AiIncidentResponse => AiIncidentResponse::fromIncident($incident),
            $this->incidents->findByAiSystem($aiSystemId),
        );
    }
}
