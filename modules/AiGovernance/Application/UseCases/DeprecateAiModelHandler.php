<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Commands\DeprecateAiModelCommand;
use Modules\AiGovernance\Application\Exceptions\AiModelNotFound;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final readonly class DeprecateAiModelHandler
{
    public function __construct(private AiModelRepository $models) {}

    public function handle(DeprecateAiModelCommand $command): AiModelResponse
    {
        $model = $this->models->findById(AiModelId::fromString($command->modelId));
        if ($model === null) {
            throw AiModelNotFound::withId($command->modelId);
        }

        $model->deprecate();
        $this->models->save($model);

        return AiModelResponse::fromModel($model);
    }
}
