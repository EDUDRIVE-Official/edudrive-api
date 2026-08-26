<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class DeviceIdentifier
{
    private const int MAX_LENGTH = 100;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('El identificador del dispositivo no puede estar vacio.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El identificador del dispositivo no puede superar %d caracteres.', self::MAX_LENGTH),
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
