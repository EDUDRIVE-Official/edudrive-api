<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class SingleChoiceResponse implements QuestionResponse
{
    public function __construct(
        public string $optionId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        self::assertShape($data, ['type', 'optionId']);

        $optionId = $data['optionId'] ?? null;
        if (! is_string($optionId) || trim($optionId) === '') {
            throw InvalidQuestion::create();
        }

        return new self(trim($optionId));
    }

    /** @return array{type: string, optionId: string} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::SingleChoice->value,
            'optionId' => $this->optionId,
        ];
    }

    /** @param array<string, mixed> $data
     *  @param  list<string>  $allowed */
    private static function assertShape(array $data, array $allowed): void
    {
        if (($data['type'] ?? null) !== QuestionType::SingleChoice->value
            || array_diff(array_keys($data), $allowed) !== []
        ) {
            throw InvalidQuestion::create();
        }
    }
}
