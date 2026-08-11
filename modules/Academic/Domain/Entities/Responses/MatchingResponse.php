<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final readonly class MatchingResponse implements QuestionResponse
{
    /** @param list<array{leftId: string, rightId: string}> $pairs */
    public function __construct(
        public array $pairs,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (($data['type'] ?? null) !== QuestionType::Matching->value
            || array_diff(array_keys($data), ['type', 'pairs']) !== []
        ) {
            throw InvalidQuestion::create();
        }

        $pairs = $data['pairs'] ?? null;
        if (! is_array($pairs) || $pairs === []) {
            throw InvalidQuestion::create();
        }

        $normalized = [];
        $leftIds = [];
        $rightIds = [];
        foreach ($pairs as $pair) {
            if (! is_array($pair)) {
                throw InvalidQuestion::create();
            }
            $leftId = $pair['leftId'] ?? null;
            $rightId = $pair['rightId'] ?? null;
            if (! is_string($leftId) || trim($leftId) === '' || ! is_string($rightId) || trim($rightId) === '') {
                throw InvalidQuestion::create();
            }
            $normalized[] = ['leftId' => trim($leftId), 'rightId' => trim($rightId)];
            $leftIds[] = trim($leftId);
            $rightIds[] = trim($rightId);
        }

        if (count(array_unique($leftIds)) !== count($leftIds) || count(array_unique($rightIds)) !== count($rightIds)) {
            throw InvalidQuestion::create();
        }

        return new self($normalized);
    }

    /** @return array{type: string, pairs: list<array{leftId: string, rightId: string}>} */
    public function toArray(): array
    {
        return [
            'type' => QuestionType::Matching->value,
            'pairs' => $this->pairs,
        ];
    }
}
