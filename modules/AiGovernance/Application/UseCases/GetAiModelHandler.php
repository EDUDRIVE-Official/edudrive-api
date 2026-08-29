<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiModelNotFound;
use Modules\AiGovernance\Application\Queries\GetAiModelQuery;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final readonly class GetAiModelHandler
{
    public function __construct(private AiModelRepository $models) {}

    public function handle(GetAiModelQuery $query): AiModelResponse
    {
        $model = $this->models->findById(AiModelId::fromString($query->modelId));
        if ($model === null) {
            throw AiModelNotFound::withId($query->modelId);
        }

        return AiModelResponse::fromModel($model);
    }
}
