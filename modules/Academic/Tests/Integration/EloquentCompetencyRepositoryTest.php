<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCompetencyRepository;

it('guarda y reconstruye el catálogo jerárquico en su orden explícito', function (): void {
    $repository = app(EloquentCompetencyRepository::class);
    $competency = Competency::create(
        id: CompetencyId::fromString('019c251d-e284-7ef8-bf25-227d5a847492'),
        code: CompetencyCode::fromString('RISK-002'),
        title: 'Gestión preventiva del riesgo',
        description: 'Adapta la conducción a riesgos previsibles.',
        category: CompetencyCategory::RiskManagement,
        masteryLevel: MasteryLevel::Developing,
    );
    $competency->addSubcompetency('RISK-002.01', 'Identificación de riesgos');
    $competency->addSubcompetency('RISK-002.02', 'Respuesta preventiva');
    $competency->addIndicator('RISK-002.01', 'RISK-002.01.I01', 'Identifica peligros visibles.');
    $competency->addIndicator('RISK-002.01', 'RISK-002.01.I02', 'Reconoce riesgos emergentes.');

    $repository->save($competency);

    $stored = $repository->findById($competency->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->category())->toBe(CompetencyCategory::RiskManagement)
        ->and($stored?->masteryLevel())->toBe(MasteryLevel::Developing)
        ->and($stored?->isActive())->toBeTrue()
        ->and($stored?->subcompetencies())->toHaveCount(2)
        ->and($stored?->subcompetencies()[0]->code())->toBe('RISK-002.01')
        ->and($stored?->subcompetencies()[1]->code())->toBe('RISK-002.02')
        ->and($stored?->subcompetencies()[0]->indicators())->toHaveCount(2)
        ->and($stored?->subcompetencies()[0]->indicators()[0]->code())->toBe('RISK-002.01.I01')
        ->and($stored?->subcompetencies()[0]->indicators()[1]->code())->toBe('RISK-002.01.I02')
        ->and($repository->existsByCode(CompetencyCode::fromString('risk-002')))->toBeTrue()
        ->and($repository->all())->toHaveCount(1);
});
