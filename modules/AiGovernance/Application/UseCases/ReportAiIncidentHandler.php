<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ReportAiIncidentCommand;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Exceptions\InvalidAiIncidentSeverity;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class ReportAiIncidentHandler
{
    public function __construct(
        private AiSystemRepository $systems,
        private AiIncidentRepository $incidents,
    ) {}

    public function handle(ReportAiIncidentCommand $command): AiIncidentResponse
    {
        $severity = AiIncidentSeverity::tryFrom($command->severity);
        if ($severity === null) {
            throw InvalidAiIncidentSeverity::withValue($command->severity);
        }

        $aiSystemId = AiSystemId::fromString($command->aiSystemId);
        if ($this->systems->findById($aiSystemId) === null) {
            throw AiSystemNotFound::withId($command->aiSystemId);
        }

        $incident = AiIncident::report(
            id: (string) Str::uuid(),
            aiSystemId: $aiSystemId->value(),
            severity: $severity,
            description: $command->description,
        );

        $this->incidents->save($incident);

        return AiIncidentResponse::fromIncident($incident);
    }
}
