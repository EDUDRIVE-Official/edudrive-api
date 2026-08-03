<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;

final class Subcompetency
{
    /** @param list<CompetencyIndicator> $indicators */
    private function __construct(
        private readonly string $code,
        private readonly string $title,
        private readonly int $position,
        private array $indicators,
    ) {}

    public static function create(string $code, string $title, int $position): self
    {
        return new self(self::normalizeCode($code), self::requireTitle($title), $position, []);
    }

    /** @param list<CompetencyIndicator> $indicators */
    public static function restore(string $code, string $title, int $position, array $indicators): self
    {
        return new self(self::normalizeCode($code), self::requireTitle($title), $position, $indicators);
    }

    public function addIndicator(string $code, string $description): void
    {
        $code = CompetencyIndicator::normalizeCode($code);
        foreach ($this->indicators as $indicator) {
            if ($indicator->code() === $code) {
                throw new InvalidArgumentException('El código del indicador ya existe en la subcompetencia.');
            }
        }
        $this->indicators[] = CompetencyIndicator::create($code, $description, count($this->indicators) + 1);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function position(): int
    {
        return $this->position;
    }

    /** @return list<CompetencyIndicator> */
    public function indicators(): array
    {
        return $this->indicators;
    }

    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen($code) > 70 || preg_match('/^[A-Z0-9]+(?:[.-][A-Z0-9]+)*$/', $code) !== 1) {
            throw new InvalidArgumentException('El código de la subcompetencia no es válido.');
        }

        return $code;
    }

    private static function requireTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException('El nombre de la subcompetencia no puede estar vacío.');
        }

        return $title;
    }
}
