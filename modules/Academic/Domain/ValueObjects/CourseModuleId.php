<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CourseModuleId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (! self::isValidUuid($value)) {
            throw new InvalidArgumentException('El identificador del modulo debe ser un UUID valido.');
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

    private static function isValidUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $value,
        ) === 1;
    }
}
