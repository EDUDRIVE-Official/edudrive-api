<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\QuestionOptionModel;

final readonly class EloquentQuestionRepository implements QuestionRepository
{
    public function save(Question $question): void
    {
        DB::transaction(function () use ($question): void {
            $model = QuestionModel::query()->updateOrCreate(
                ['id' => $question->id()->value()],
                [
                    'competency_id' => $question->competencyId()->value(),
                    'type' => $question->type()->value,
                    'prompt' => $question->prompt(),
                    'explanation' => $question->explanation(),
                    'score' => $question->score(),
                    'media' => array_map(
                        static fn (QuestionMedia $media): array => $media->toArray(),
                        $question->media(),
                    ),
                    'response' => $question->response()->toArray(),
                ],
            );

            $model->options()->delete();

            foreach ($question->options() as $option) {
                QuestionOptionModel::query()->create([
                    'id' => (string) Str::uuid(),
                    'question_id' => $model->id,
                    'ref_id' => $option->refId(),
                    'side' => $option->side(),
                    'label' => $option->label(),
                    'position' => $option->position(),
                ]);
            }
        });
    }

    public function findById(QuestionId $id): ?Question
    {
        $model = QuestionModel::query()->with('options')->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<Question> */
    public function all(?CompetencyId $competencyId = null): array
    {
        $builder = QuestionModel::query()->with('options');
        if ($competencyId !== null) {
            $builder->where('competency_id', $competencyId->value());
        }

        return array_values(
            $builder->orderBy('created_at')->get()
                ->map(fn (QuestionModel $model): Question => $this->toDomain($model))
                ->all(),
        );
    }

    public function delete(QuestionId $id): void
    {
        QuestionModel::query()->where('id', $id->value())->delete();
    }

    private function toDomain(QuestionModel $model): Question
    {
        /** @var array<string, mixed> $response */
        $response = $model->getAttribute('response');

        /** @var list<array{type: string, url: string}> $media */
        $media = $model->getAttribute('media') ?? [];

        $options = array_values($model->options->map(fn (QuestionOptionModel $option): QuestionOption => QuestionOption::create(
            refId: (string) $option->getAttribute('ref_id'),
            id: QuestionOptionId::fromString((string) $option->getAttribute('id')),
            position: (int) $option->getAttribute('position'),
            label: (string) $option->getAttribute('label'),
            side: $option->getAttribute('side') === null ? null : (string) $option->getAttribute('side'),
        ))->all());

        return Question::restore(
            QuestionId::fromString((string) $model->getAttribute('id')),
            QuestionType::from((string) $model->getAttribute('type')),
            CompetencyId::fromString((string) $model->getAttribute('competency_id')),
            (string) $model->getAttribute('prompt'),
            (int) $model->getAttribute('score'),
            QuestionResponseFactory::fromPayload((string) $model->getAttribute('type'), $response),
            $options,
            $model->getAttribute('explanation') === null ? null : (string) $model->getAttribute('explanation'),
            array_map(static fn (array $m): QuestionMedia => QuestionMedia::fromArray($m), $media),
        );
    }
}
