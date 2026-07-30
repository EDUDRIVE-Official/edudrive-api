<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class OrganizationName
{
    private const int MAX_LENGTH = 180;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('El nombre de la organización no puede estar vacío.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El nombre de la organización no puede superar %d caracteres.', self::MAX_LENGTH),
            );
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
