<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CommunicationTemplateCode
{
    private const int MAX_LENGTH = 50;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            throw new InvalidArgumentException('El codigo de la plantilla no puede estar vacio.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El codigo de la plantilla no puede superar %d caracteres.', self::MAX_LENGTH),
            );
        }

        if (preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'El codigo de la plantilla solo puede contener letras, numeros y guiones intermedios.',
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
