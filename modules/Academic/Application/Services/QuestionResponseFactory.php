<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Exceptions\InvalidQuestion;

final class QuestionResponseFactory
{
    /** @param array<string, mixed> $data */
    public static function fromPayload(string $type, array $data): QuestionResponse
    {
        if ($type === 'situational') {
            return self::situationalFromPayload($data);
        }

        $data = ['type' => $type] + $data;

        return match ($type) {
            'single_choice' => SingleChoiceResponse::fromArray($data),
            'multi_select' => MultiSelectResponse::fromArray($data),
            'true_false' => TrueFalseResponse::fromArray($data),
            'matching' => MatchingResponse::fromArray($data),
            'ordering' => OrderingResponse::fromArray($data),
            default => throw InvalidQuestion::create(),
        };
    }

    /** @param array<string, mixed> $data */
    private static function situationalFromPayload(array $data): QuestionResponse
    {
        $innerType = $data['type'] ?? null;
        if (! is_string($innerType) || $innerType === 'situational') {
            throw InvalidQuestion::create();
        }

        return self::fromPayload($innerType, $data);
    }
}