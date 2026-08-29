<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Enums\AiPromptStatus;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models\AiPromptModel;

final readonly class EloquentAiPromptRepository implements AiPromptRepository
{
    public function save(AiPrompt $prompt): void
    {
        AiPromptModel::query()->updateOrCreate(
            ['id' => $prompt->id()->value()],
            [
                'identifier' => $prompt->identifier(),
                'purpose' => $prompt->purpose(),
                'model_id' => $prompt->modelId(),
                'version' => $prompt->version(),
                'author_id' => $prompt->authorId(),
                'content' => $prompt->content(),
                'status' => $prompt->status()->value,
            ],
        );
    }

    public function findById(AiPromptId $id): ?AiPrompt
    {
        $model = AiPromptModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<AiPrompt> */
    public function all(): array
    {
        return array_values(
            AiPromptModel::query()
                ->orderBy('created_at')
                ->get()
                ->map(fn (AiPromptModel $model): AiPrompt => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(AiPromptModel $model): AiPrompt
    {
        return AiPrompt::restore(
            id: AiPromptId::fromString((string) $model->getAttribute('id')),
            identifier: (string) $model->getAttribute('identifier'),
            purpose: (string) $model->getAttribute('purpose'),
            modelId: $model->getAttribute('model_id') === null ? null : (string) $model->getAttribute('model_id'),
            version: (int) $model->getAttribute('version'),
            authorId: $model->getAttribute('author_id') === null ? null : (string) $model->getAttribute('author_id'),
            content: (string) $model->getAttribute('content'),
            status: AiPromptStatus::from((string) $model->getAttribute('status')),
            createdAt: new DateTimeImmutable((string) $model->getAttribute('created_at')),
        );
    }
}
