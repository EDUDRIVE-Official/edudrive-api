<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use Modules\Academic\Domain\Exceptions\InvalidContentBlock;

final readonly class MimeType
{
    private const int MAX_LENGTH = 255;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (
            $value === ''
            || strlen($value) > self::MAX_LENGTH
            || preg_match('/\A[!#$%&\'*+\-.^_`|~0-9A-Za-z]+\/[!#$%&\'*+\-.^_`|~0-9A-Za-z]+\z/D', $value) !== 1
        ) {
            throw InvalidContentBlock::create();
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
