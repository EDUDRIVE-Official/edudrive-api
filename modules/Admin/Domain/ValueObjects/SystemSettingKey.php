<?php

declare(strict_types=1);

namespace Modules\Admin\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class SystemSettingKey
{
    private const int MAX_LENGTH = 100;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La clave de configuración no puede superar %d caracteres.', self::MAX_LENGTH),
            );
        }

        if (preg_match('/^[a-z][a-z0-9_]*$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'La clave de configuración debe estar en minúsculas, iniciar con una letra y usar solo letras, números y guiones bajos.',
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
