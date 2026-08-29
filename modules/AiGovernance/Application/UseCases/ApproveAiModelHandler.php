<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Commands\ApproveAiModelCommand;
use Modules\AiGovernance\Application\Exceptions\AiModelNotFound;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final readonly class ApproveAiModelHandler
{
    public function __construct(private AiModelRepository $models) {}

    public function handle(ApproveAiModelCommand $command): AiModelResponse
    {
        $model = $this->models->findById(AiModelId::fromString($command->modelId));
        if ($model === null) {
            throw AiModelNotFound::withId($command->modelId);
        }

        $model->approve();
        $this->models->save($model);

        return AiModelResponse::fromModel($model);
    }
}
