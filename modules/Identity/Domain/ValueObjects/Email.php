<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\ValueObjects;

use Modules\Identity\Domain\Exceptions\InvalidEmail;
use Stringable;

final readonly class Email implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $normalizedValue = mb_strtolower(trim($value));

        if (! filter_var($normalizedValue, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::fromValue($value);
        }

        $this->value = $normalizedValue;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
