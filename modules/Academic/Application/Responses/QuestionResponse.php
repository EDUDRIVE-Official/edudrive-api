<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\ValueObjects\QuestionMedia;

final readonly class QuestionResponse
{
    /** @param list<array{refId: string, label: string, position: int, side: string|null}> $options
     *  @param  array<string, mixed>  $correct
     *  @param  list<array{type: string, url: string}>  $media */
    private function __construct(
        public string $id,
        public string $type,
        public string $competencyId,
        public string $prompt,
        public int $score,
        public ?string $explanation,
        public array $options,
        public array $correct,
        public array $media,
    ) {}

    public static function fromQuestion(Question $question): self
    {
        return new self(
            $question->id()->value(),
            $question->type()->value,
            $question->competencyId()->value(),
            $question->prompt(),
            $question->score(),
            $question->explanation(),
            array_map(static fn (QuestionOption $option): array => [
                'refId' => $option->refId(),
                'label' => $option->label(),
                'position' => $option->position(),
                'side' => $option->side(),
            ], $question->options()),
            $question->response()->toArray(),
            array_map(static fn (QuestionMedia $media): array => $media->toArray(), $question->media()),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     competency_id: string,
     *     prompt: string,
     *     score: int,
     *     explanation: string|null,
     *     options: list<array{refId: string, label: string, position: int, side: string|null}>,
     *     correct: array<string, mixed>,
     *     media: list<array{type: string, url: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'competency_id' => $this->competencyId,
            'prompt' => $this->prompt,
            'score' => $this->score,
            'explanation' => $this->explanation,
            'options' => $this->options,
            'correct' => $this->correct,
            'media' => $this->media,
        ];
    }
}