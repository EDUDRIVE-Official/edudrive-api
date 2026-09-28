<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\AiGovernance\Application\Queries\ListAiIncidentsQuery;
use Modules\AiGovernance\Application\Queries\ListAiModelsQuery;
use Modules\AiGovernance\Application\Queries\ListAiPromptsQuery;
use Modules\AiGovernance\Application\Queries\ListAiProviderEvaluationsQuery;
use Modules\AiGovernance\Application\Queries\ListAiSystemsQuery;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class AiGovernanceWebController
{
    public function __invoke(QueryBus $queryBus): View
    {
        $systems = $queryBus->ask(new ListAiSystemsQuery);
        $models = $queryBus->ask(new ListAiModelsQuery);
        $prompts = $queryBus->ask(new ListAiPromptsQuery);
        $providers = $queryBus->ask(new ListAiProviderEvaluationsQuery);
        assert(is_array($systems));
        assert(is_array($models));
        assert(is_array($prompts));
        assert(is_array($providers));

        $incidents = [];
        foreach ($systems as $system) {
            assert($system instanceof AiSystemResponse);
            $systemIncidents = $queryBus->ask(new ListAiIncidentsQuery(aiSystemId: $system->id));
            assert(is_array($systemIncidents));
            array_push($incidents, ...$systemIncidents);
        }
        $systemNames = collect($systems)->mapWithKeys(
            static fn (AiSystemResponse $system): array => [$system->id => $system->name],
        );

        return view('ai-governance.dashboard', [
            'systems' => array_map(static fn (AiSystemResponse $system): array => $system->toArray(), $systems),
            'models' => array_map(static fn (AiModelResponse $model): array => $model->toArray(), $models),
            'prompts' => array_map(static fn (AiPromptResponse $prompt): array => $prompt->toArray(), $prompts),
            'providers' => array_map(static fn (AiProviderEvaluationResponse $provider): array => $provider->toArray(), $providers),
            'incidents' => array_map(static function (AiIncidentResponse $incident) use ($systemNames): array {
                $data = $incident->toArray();
                $data['system_name'] = $systemNames->get($incident->aiSystemId, 'Sistema no disponible');

                return $data;
            }, $incidents),
        ]);
    }
}
