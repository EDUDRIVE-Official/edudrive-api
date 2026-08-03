<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;

final readonly class CompetencyIndicator
{
    private function __construct(
        private string $code,
        private string $description,
        private int $position,
    ) {}

    public static function create(string $code, string $description, int $position): self
    {
        return new self(
            self::normalizeCode($code),
            self::requireText($description, 'La descripción del indicador no puede estar vacía.'),
            $position,
        );
    }

    public static function restore(string $code, string $description, int $position): self
    {
        return self::create($code, $description, $position);
    }

    public function code(): string { return $this->code; }
    public function description(): string { return $this->description; }
    public function position(): int { return $this->position; }

    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen($code) > 80 || preg_match('/^[A-Z0-9]+(?:[.-][A-Z0-9]+)*$/', $code) !== 1) {
            throw new InvalidArgumentException('El código del indicador no es válido.');
        }
        return $code;
    }

    private static function requireText(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') { throw new InvalidArgumentException($message); }
        return $value;
    }
}
