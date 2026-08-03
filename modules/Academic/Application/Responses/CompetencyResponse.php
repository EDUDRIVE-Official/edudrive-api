<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Entities\CompetencyIndicator;
use Modules\Academic\Domain\Entities\Subcompetency;

final readonly class CompetencyResponse
{
    /** @param list<array{code: string, title: string, position: int, indicators: list<array{code: string, description: string, position: int}>}> $subcompetencies */
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $description,
        public string $category,
        public string $masteryLevel,
        public string $status,
        public array $subcompetencies,
    ) {}

    public static function fromCompetency(Competency $competency): self
    {
        return new self(
            $competency->id()->value(),
            $competency->code()->value(),
            $competency->title(),
            $competency->description(),
            $competency->category()->value,
            $competency->masteryLevel()->value,
            $competency->isActive() ? 'active' : 'inactive',
            array_map(static fn (Subcompetency $subcompetency): array => [
                'code' => $subcompetency->code(),
                'title' => $subcompetency->title(),
                'position' => $subcompetency->position(),
                'indicators' => array_map(static fn (CompetencyIndicator $indicator): array => [
                    'code' => $indicator->code(),
                    'description' => $indicator->description(),
                    'position' => $indicator->position(),
                ], $subcompetency->indicators()),
            ], $competency->subcompetencies()),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'mastery_level' => $this->masteryLevel,
            'status' => $this->status,
            'subcompetencies' => $this->subcompetencies,
        ];
    }
}
