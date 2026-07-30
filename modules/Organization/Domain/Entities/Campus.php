<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Entities;

use InvalidArgumentException;

final class Campus
{
    private function __construct(
        private readonly string $id,
        private string $name,
    ) {}

    public static function create(string $id, string $name): self
    {
        return new self(
            id: $id,
            name: self::normalizeName($name),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    private static function normalizeName(string $name): string
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new InvalidArgumentException('El nombre de la sede no puede estar vacío.');
        }

        if (mb_strlen($normalizedName) > 180) {
            throw new InvalidArgumentException('El nombre de la sede no puede superar 180 caracteres.');
        }

        return $normalizedName;
    }
}
