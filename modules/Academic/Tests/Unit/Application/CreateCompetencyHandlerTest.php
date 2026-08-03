<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Exceptions\CompetencyCodeAlreadyExists;
use Modules\Academic\Application\UseCases\CreateCompetencyHandler;
use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

function inMemoryCompetencyRepository(): CompetencyRepository
{
    return new class implements CompetencyRepository
    {
        /** @var array<string, Competency> */
        private array $competencies = [];

        public function save(Competency $competency): void { $this->competencies[$competency->code()->value()] = $competency; }
        public function findById(CompetencyId $id): ?Competency
        {
            foreach ($this->competencies as $competency) {
                if ($competency->id()->equals($id)) { return $competency; }
            }
            return null;
        }
        public function findByCode(CompetencyCode $code): ?Competency { return $this->competencies[$code->value()] ?? null; }
        public function existsByCode(CompetencyCode $code): bool { return isset($this->competencies[$code->value()]); }
        public function all(): array { return array_values($this->competencies); }
    };
}

it('normaliza el código y devuelve la representación pública de la competencia', function (): void {
    $handler = new CreateCompetencyHandler(inMemoryCompetencyRepository());

    $response = $handler->handle(new CreateCompetencyCommand(
        code: ' risk-010 ',
        title: 'Gestión del riesgo',
        description: 'Anticipa riesgos viales.',
        category: 'risk_management',
        masteryLevel: 'foundation',
    ));

    expect($response->toArray())
        ->toMatchArray([
            'code' => 'RISK-010',
            'category' => 'risk_management',
            'mastery_level' => 'foundation',
            'status' => 'active',
            'subcompetencies' => [],
        ]);
});

it('rechaza un código de competencia existente', function (): void {
    $repository = inMemoryCompetencyRepository();
    $handler = new CreateCompetencyHandler($repository);
    $command = new CreateCompetencyCommand('RISK-010', 'Gestión del riesgo', 'Anticipa riesgos.', 'risk_management', 'foundation');
    $handler->handle($command);

    $handler->handle($command);
})->throws(CompetencyCodeAlreadyExists::class, 'Ya existe una competencia con el código RISK-010.');
