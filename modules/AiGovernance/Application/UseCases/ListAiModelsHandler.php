<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Queries\ListAiModelsQuery;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;

final readonly class ListAiModelsHandler
{
    public function __construct(private AiModelRepository $models) {}

    /** @return list<AiModelResponse> */
    public function handle(ListAiModelsQuery $query): array
    {
        return array_map(
            static fn (AiModel $model): AiModelResponse => AiModelResponse::fromModel($model),
            $this->models->all(),
        );
    }
}
