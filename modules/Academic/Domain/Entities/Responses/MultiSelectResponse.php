<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class MultiSelectResponse implements QuestionResponse
{
    /** @param list<string> $optionIds */
    public function __construct(
        public array $optionIds,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        self::assertShape($data, ['type', 'optionIds']);

        $optionIds = $data['optionIds'] ?? null;
        if (! is_array($optionIds) || $optionIds === []) {
            throw InvalidQuestion::create();
        }

        $ids = [];
        foreach ($optionIds as $optionId) {
            if (! is_string($optionId) || trim($optionId) === '') {
                throw InvalidQuestion::create();
            }
            $ids[] = trim($optionId);
        }

        if (count(array_unique($ids)) !== count($ids)) {
            throw InvalidQuestion::create();
        }

        return new self($ids);
    }

    /** @return array{type: string, optionIds: list<string>} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::MultiSelect->value,
            'optionIds' => $this->optionIds,
        ];
    }

    /** @param array<string, mixed> $data
     *  @param  list<string>  $allowed */
    private static function assertShape(array $data, array $allowed): void
    {
        if (($data['type'] ?? null) !== QuestionType::MultiSelect->value
            || array_diff(array_keys($data), $allowed) !== []
        ) {
            throw InvalidQuestion::create();
        }
    }
}
