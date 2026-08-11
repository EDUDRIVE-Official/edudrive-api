<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class OrderingResponse implements QuestionResponse
{
    /** @param list<string> $itemIds */
    public function __construct(
        public array $itemIds,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (($data['type'] ?? null) !== QuestionType::Ordering->value
            || array_diff(array_keys($data), ['type', 'itemIds']) !== []
        ) {
            throw InvalidQuestion::create();
        }

        $itemIds = $data['itemIds'] ?? null;
        if (! is_array($itemIds) || count($itemIds) < 2) {
            throw InvalidQuestion::create();
        }

        $ids = [];
        foreach ($itemIds as $itemId) {
            if (! is_string($itemId) || trim($itemId) === '') {
                throw InvalidQuestion::create();
            }
            $ids[] = trim($itemId);
        }

        if (count(array_unique($ids)) !== count($ids)) {
            throw InvalidQuestion::create();
        }

        return new self(array_values($ids));
    }

    /** @return array{type: string, itemIds: list<string>} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::Ordering->value,
            'itemIds' => $this->itemIds,
        ];
    }
}