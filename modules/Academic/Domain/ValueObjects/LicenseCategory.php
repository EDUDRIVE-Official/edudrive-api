<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class LicenseCategory
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            throw new InvalidArgumentException('La categoria de licencia no puede estar vacia.');
        }

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
