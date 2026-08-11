<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Exceptions\InvalidQuestion;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final class QuestionOption
{
    public const array ALLOWED_SIDES = ['left', 'right'];

    private function __construct(
        private readonly string $refId,
        private readonly QuestionOptionId $id,
        private readonly string $label,
        private readonly int $position,
        private readonly ?string $side,
    ) {}

    public static function create(
        string $refId,
        QuestionOptionId $id,
        int $position,
        string $label,
        ?string $side = null,
    ): self {
        $label = trim($label);
        if ($label === '' || strlen($label) > 500) {
            throw InvalidQuestion::create();
        }
        if ($position < 1) {
            throw InvalidQuestion::create();
        }
        if ($side !== null && ! in_array($side, self::ALLOWED_SIDES, true)) {
            throw InvalidQuestion::create();
        }

        return new self($refId, $id, $label, $position, $side);
    }

    public function refId(): string
    {
        return $this->refId;
    }

    public function id(): QuestionOptionId
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function side(): ?string
    {
        return $this->side;
    }
}
