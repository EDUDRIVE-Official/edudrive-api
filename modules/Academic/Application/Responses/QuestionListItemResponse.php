<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Question;

final readonly class QuestionListItemResponse
{
    private function __construct(
        public string $id,
        public string $type,
        public string $competencyId,
        public string $prompt,
        public int $score,
    ) {}

    public static function fromQuestion(Question $question): self
    {
        return new self(
            $question->id()->value(),
            $question->type()->value,
            $question->competencyId()->value(),
            $question->prompt(),
            $question->score(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     competency_id: string,
     *     prompt: string,
     *     score: int
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
        ];
    }
}
