<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ProgramCode
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));

        if ($value === '' || mb_strlen($value) > 60) {
            throw new InvalidArgumentException('El codigo del programa debe tener entre 1 y 60 caracteres.');
        }

        if (preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $value) !== 1) {
            throw new InvalidArgumentException('El codigo del programa solo puede contener letras, numeros y guiones intermedios.');
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
