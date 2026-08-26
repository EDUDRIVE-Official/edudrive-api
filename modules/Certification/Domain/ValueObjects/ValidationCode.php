<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ValidationCode
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private function __construct(private string $value) {}

    public static function generate(): self
    {
        $groups = [];
        for ($group = 0; $group < 3; $group++) {
            $chars = '';
            for ($i = 0; $i < 4; $i++) {
                $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $groups[] = $chars;
        }

        return new self(implode('-', $groups));
    }

    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));

        if (preg_match('/^['.self::ALPHABET.']{4}-['.self::ALPHABET.']{4}-['.self::ALPHABET.']{4}$/', $value) !== 1) {
            throw new InvalidArgumentException('El codigo de validacion no tiene un formato valido.');
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
