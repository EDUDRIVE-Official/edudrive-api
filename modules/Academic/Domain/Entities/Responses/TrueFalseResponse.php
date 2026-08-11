<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class TrueFalseResponse implements QuestionResponse
{
    public function __construct(
        public bool $correct,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (($data['type'] ?? null) !== QuestionType::TrueFalse->value
            || array_diff(array_keys($data), ['type', 'correct']) !== []
        ) {
            throw InvalidQuestion::create();
        }

        $correct = $data['correct'] ?? null;
        if (! is_bool($correct)) {
            throw InvalidQuestion::create();
        }

        return new self($correct);
    }

    public function matches(QuestionResponse $other): bool
    {
        return $other instanceof self && $this->correct === $other->correct;
    }

    /** @return array{type: string, correct: bool} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::TrueFalse->value,
            'correct' => $this->correct,
        ];
    }
}
