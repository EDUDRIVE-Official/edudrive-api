<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use Modules\Academic\Domain\Exceptions\InvalidContentBlock;

final readonly class ExternalContentUrl
{
    private const int MAX_LENGTH = 2048;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);
        $parts = parse_url($value);

        if (
            $value === ''
            || strlen($value) > self::MAX_LENGTH
            || filter_var($value, FILTER_VALIDATE_URL) === false
            || ! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || trim((string) ($parts['host'] ?? '')) === ''
            || isset($parts['user'])
            || isset($parts['pass'])
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
