<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DomainException;
use InvalidArgumentException;
use Modules\Academic\Domain\Entities\Subcompetency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final class Competency
{
    /** @param list<Subcompetency> $subcompetencies */
    private function __construct(
        private readonly CompetencyId $id,
        private readonly CompetencyCode $code,
        private readonly string $title,
        private readonly string $description,
        private readonly CompetencyCategory $category,
        private readonly MasteryLevel $masteryLevel,
        private bool $active,
        private array $subcompetencies,
    ) {}

    public static function create(CompetencyId $id, CompetencyCode $code, string $title, string $description, CompetencyCategory $category, MasteryLevel $masteryLevel): self
    {
        return new self($id, $code, self::requireText($title, 'El nombre de la competencia no puede estar vacío.'), self::requireText($description, 'La descripción de la competencia no puede estar vacía.'), $category, $masteryLevel, true, []);
    }

    /** @param list<Subcompetency> $subcompetencies */
    public static function restore(CompetencyId $id, CompetencyCode $code, string $title, string $description, CompetencyCategory $category, MasteryLevel $masteryLevel, bool $active, array $subcompetencies): self
    {
        return new self($id, $code, self::requireText($title, 'El nombre de la competencia no puede estar vacío.'), self::requireText($description, 'La descripción de la competencia no puede estar vacía.'), $category, $masteryLevel, $active, $subcompetencies);
    }

    public function addSubcompetency(string $code, string $title): void
    {
        $this->ensureIsActive();
        $code = Subcompetency::normalizeCode($code);
        foreach ($this->subcompetencies as $subcompetency) {
            if ($subcompetency->code() === $code) {
                throw new InvalidArgumentException('El código de la subcompetencia ya existe.');
            }
        }
        $this->subcompetencies[] = Subcompetency::create($code, $title, count($this->subcompetencies) + 1);
    }

    public function addIndicator(string $subcompetencyCode, string $code, string $description): void
    {
        $this->ensureIsActive();
        $this->findSubcompetency($subcompetencyCode)->addIndicator($code, $description);
    }

    public function id(): CompetencyId
    {
        return $this->id;
    }

    public function code(): CompetencyCode
    {
        return $this->code;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function category(): CompetencyCategory
    {
        return $this->category;
    }

    public function masteryLevel(): MasteryLevel
    {
        return $this->masteryLevel;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /** @return list<Subcompetency> */
    public function subcompetencies(): array
    {
        return $this->subcompetencies;
    }

    private function findSubcompetency(string $code): Subcompetency
    {
        $code = Subcompetency::normalizeCode($code);
        foreach ($this->subcompetencies as $subcompetency) {
            if ($subcompetency->code() === $code) {
                return $subcompetency;
            }
        }
        throw new InvalidArgumentException('La subcompetencia no existe.');
    }

    private function ensureIsActive(): void
    {
        if (! $this->active) {
            throw new DomainException('Una competencia inactiva no puede ser modificada.');
        }
    }

    private static function requireText(string $value, string $message): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }
}
