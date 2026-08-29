<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\RegisterAiModelCommand;
use Modules\AiGovernance\Application\Responses\AiModelResponse;
use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

final readonly class RegisterAiModelHandler
{
    public function __construct(private AiModelRepository $models) {}

    public function handle(RegisterAiModelCommand $command): AiModelResponse
    {
        $model = AiModel::register(
            id: AiModelId::fromString((string) Str::uuid()),
            name: $command->name,
            provider: $command->provider,
            version: $command->version,
            ownerId: $command->ownerId,
            useCase: $command->useCase,
            knownRisks: $command->knownRisks,
        );

        $this->models->save($model);

        return AiModelResponse::fromModel($model);
    }
}
