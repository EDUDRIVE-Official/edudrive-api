<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

function createRoadCompetency(): Competency
{
    return Competency::create(
        id: CompetencyId::fromString('019c251d-e284-7ef8-bf25-227d5a847491'),
        code: CompetencyCode::fromString(' risk-001 '),
        title: 'Anticipación y gestión del riesgo vial',
        description: 'Reconoce y responde a riesgos del entorno vial.',
        category: CompetencyCategory::RiskManagement,
        masteryLevel: MasteryLevel::Foundation,
    );
}

it('crea una competencia activa con código normalizado', function (): void {
    $competency = createRoadCompetency();

    expect($competency->code()->value())->toBe('RISK-001')
        ->and($competency->isActive())->toBeTrue()
        ->and($competency->subcompetencies())->toBeEmpty();
});

it('agrega una subcompetencia con código normalizado', function (): void {
    $competency = createRoadCompetency();

    $competency->addSubcompetency(' risk-001.01 ', 'Observación del entorno');

    expect($competency->subcompetencies())->toHaveCount(1)
        ->and($competency->subcompetencies()[0]->code())->toBe('RISK-001.01')
        ->and($competency->subcompetencies()[0]->position())->toBe(1);
});

it('rechaza subcompetencias con código duplicado', function (): void {
    $competency = createRoadCompetency();
    $competency->addSubcompetency('RISK-001.01', 'Observación del entorno');

    $competency->addSubcompetency(' risk-001.01 ', 'Otra capacidad');
})->throws(InvalidArgumentException::class, 'El código de la subcompetencia ya existe.');

it('agrega un indicador observable a una subcompetencia', function (): void {
    $competency = createRoadCompetency();
    $competency->addSubcompetency('RISK-001.01', 'Observación del entorno');

    $competency->addIndicator(
        'RISK-001.01',
        'RISK-001.01.I01',
        'Anticipa riesgos visibles.',
    );

    expect($competency->subcompetencies()[0]->indicators())->toHaveCount(1)
        ->and($competency->subcompetencies()[0]->indicators()[0]->position())->toBe(1);
});

it('rechaza indicadores duplicados dentro de una subcompetencia', function (): void {
    $competency = createRoadCompetency();
    $competency->addSubcompetency('RISK-001.01', 'Observación del entorno');
    $competency->addIndicator('RISK-001.01', 'RISK-001.01.I01', 'Anticipa riesgos visibles.');

    $competency->addIndicator('RISK-001.01', ' risk-001.01.i01 ', 'Indicador repetido.');
})->throws(InvalidArgumentException::class, 'El código del indicador ya existe en la subcompetencia.');
