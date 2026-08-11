<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\UpdateQuestionCommand;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Responses\QuestionResponse;
use Modules\Academic\Application\Services\QuestionResponseFactory;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final readonly class UpdateQuestionHandler
{
    public function __construct(private QuestionRepository $questions) {}

    public function handle(UpdateQuestionCommand $command): QuestionResponse
    {
        $id = QuestionId::fromString($command->questionId);
        $question = $this->questions->findById($id);
        if ($question === null) {
            throw QuestionNotFound::withId($command->questionId);
        }

        $question->replace(
            QuestionType::from($command->type),
            $command->prompt,
            $command->score,
            QuestionResponseFactory::fromPayload($command->type, $command->response),
            array_map($this->optionMapper(), $command->options, array_keys($command->options)),
            $command->explanation,
            array_map(static fn (array $media): QuestionMedia => QuestionMedia::fromArray($media), $command->media),
        );
        $this->questions->save($question);

        return QuestionResponse::fromQuestion($question);
    }

    /** @return callable(array{refId: string, label: string, side?: string|null}): QuestionOption */
    private function optionMapper(): callable
    {
        return static fn (array $option, int $index): QuestionOption => QuestionOption::create(
            refId: (string) $option['refId'],
            id: QuestionOptionId::fromString((string) Str::uuid()),
            position: $index + 1,
            label: (string) $option['label'],
            side: isset($option['side']) ? (string) $option['side'] : null,
        );
    }
}