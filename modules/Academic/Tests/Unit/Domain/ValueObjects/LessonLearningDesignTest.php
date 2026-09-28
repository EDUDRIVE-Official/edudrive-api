<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;

it('mantiene pendiente la etapa sin inferirla del formato de actividad', function (string $experience): void {
    $design = LessonLearningDesign::fromArray([
        'stage' => 'pending_review', 'jurisdictions' => ['CR'], 'experience_type' => $experience,
        'behavior_objective' => 'Identifica un espacio protegido.',
        'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO', 'indicator_codes' => ['OBSERVA'],
        'evidence_rules' => [['indicator_code' => 'OBSERVA', 'event_type' => 'lesson_completed', 'minimum_observations' => 1, 'weight' => 1]],
    ]);

    expect($design->stage)->toBe('pending_review')
        ->and($design->experienceType)->toBe($experience)
        ->and(LessonLearningDesign::fromArray($design->toArray())->stage)->toBe('pending_review');
})->with(['story', 'guided_practice', 'competency_challenge', 'reflection']);

it('representa un contrato pedagógico trazable para una lección', function (): void {
    $design = LessonLearningDesign::fromArray([
        'stage' => 'explore',
        'jurisdictions' => ['global', 'cr'],
        'experience_type' => 'visual_exploration',
        'behavior_objective' => 'Seleccionar un lugar visible y seguro antes de cruzar.',
        'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO',
        'indicator_codes' => ['OBSERVA.VISIBILIDAD'],
        'evidence_rules' => [[
            'indicator_code' => 'OBSERVA.VISIBILIDAD',
            'event_type' => 'safe_crossing_place_selected',
            'minimum_observations' => 3,
            'weight' => 100,
        ]],
        'requires_guardian' => true,
        'normative_sources' => [],
        'version' => 1,
    ]);

    expect($design->toArray())
        ->stage->toBe('explore')
        ->jurisdictions->toBe(['GLOBAL', 'CR'])
        ->requires_guardian->toBeTrue();
});

it('rechaza un diseño pedagógico sin reglas de evidencia', function (): void {
    LessonLearningDesign::fromArray([
        'stage' => 'explore',
        'jurisdictions' => ['CR'],
        'experience_type' => 'story',
        'behavior_objective' => 'Cruzar de forma segura.',
        'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO',
        'indicator_codes' => ['OBSERVA'],
        'evidence_rules' => [],
    ]);
})->throws(InvalidArgumentException::class);

it('rechaza reglas de evidencia que no corresponden a los indicadores declarados', function (): void {
    LessonLearningDesign::fromArray([
        'stage' => 'explore', 'jurisdictions' => ['CR'], 'experience_type' => 'story',
        'behavior_objective' => 'Cruzar de forma segura.', 'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO', 'indicator_codes' => ['OBSERVA'],
        'evidence_rules' => [['indicator_code' => 'OTRO', 'event_type' => 'decision', 'minimum_observations' => 1, 'weight' => 100]],
    ]);
})->throws(InvalidArgumentException::class);

it('rechaza fuentes sin direccion segura o fecha editorial valida', function (): void {
    LessonLearningDesign::fromArray([
        'stage' => 'explore', 'jurisdictions' => ['CR'], 'experience_type' => 'story',
        'behavior_objective' => 'Cruzar de forma segura.', 'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO', 'indicator_codes' => ['OBSERVA'],
        'evidence_rules' => [['indicator_code' => 'OBSERVA', 'event_type' => 'decision', 'minimum_observations' => 1, 'weight' => 100]],
        'normative_sources' => [['url' => 'http://ejemplo.test/fuente', 'reviewed_at' => 'ayer']],
    ]);
})->throws(InvalidArgumentException::class);

it('determina la vigencia anual de las fuentes sin aceptar fechas futuras', function (): void {
    $design = LessonLearningDesign::fromArray([
        'stage' => 'explore', 'jurisdictions' => ['CR'], 'experience_type' => 'story',
        'behavior_objective' => 'Cruzar de forma segura.', 'competency_id' => '01981a64-8300-7b1d-b442-764ea7f915c0',
        'subcompetency_code' => 'CRUCE.SEGURO', 'indicator_codes' => ['OBSERVA'],
        'evidence_rules' => [['indicator_code' => 'OBSERVA', 'event_type' => 'decision', 'minimum_observations' => 1, 'weight' => 100]],
        'normative_sources' => [['url' => 'https://www.csv.go.cr/seguridad-vial', 'reviewed_at' => '2026-09-01']],
    ]);

    expect($design->sourcesAreCurrent(new DateTimeImmutable('2026-09-12')))->toBeTrue()
        ->and($design->sourcesAreCurrent(new DateTimeImmutable('2027-09-02')))->toBeFalse()
        ->and($design->sourcesAreCurrent(new DateTimeImmutable('2026-08-31')))->toBeFalse();
});
