<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumDuration;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumText;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;

function curriculumUnitId(string $value = '01981a64-8300-7b1d-b442-764ea7f915c0'): CourseUnitId
{
    return CourseUnitId::fromString($value);
}

it('normaliza el identificador de unidad y compara por valor', function (): void {
    $id = CourseUnitId::fromString(' 01981A64-8300-7B1D-B442-764EA7F915C0 ');

    expect($id->value())
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and((string) $id)
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($id->equals(curriculumUnitId()))
        ->toBeTrue();
});

it('rechaza identificadores de unidad que no son uuid', function (): void {
    CourseUnitId::fromString('unit-1');
})->throws(InvalidArgumentException::class);

it('crea una unidad curricular normalizada', function (): void {
    $prerequisite = curriculumUnitId('01981a64-8300-7b1d-b442-764ea7f915c1');

    $unit = CourseUnit::create(
        id: curriculumUnitId(),
        code: CurriculumCode::fromString(' uni-01 '),
        title: '  Señalización preventiva  ',
        description: '  Reconoce las señales preventivas.  ',
        objectives: '  Identificar riesgos en la vía.  ',
        durationMinutes: 25,
        position: 2,
        prerequisiteUnitIds: [$prerequisite],
    );

    expect($unit->id()->equals(curriculumUnitId()))
        ->toBeTrue()
        ->and($unit->code()->value())
        ->toBe('UNI-01')
        ->and($unit->title())
        ->toBe('Señalización preventiva')
        ->and($unit->description())
        ->toBe('Reconoce las señales preventivas.')
        ->and($unit->objectives())
        ->toBe('Identificar riesgos en la vía.')
        ->and($unit->durationMinutes())
        ->toBe(25)
        ->and($unit->position())
        ->toBe(2)
        ->and($unit->prerequisiteUnitIds())
        ->toBe([$prerequisite]);
});

it('convierte objetivos vacios en null y admite duracion ausente', function (): void {
    $unit = CourseUnit::create(
        id: curriculumUnitId(),
        code: CurriculumCode::fromString('UNI-01'),
        title: 'Señalización',
        description: 'Descripción',
        objectives: ' ',
        durationMinutes: null,
        position: 1,
        prerequisiteUnitIds: [],
    );

    expect($unit->objectives())->toBeNull()
        ->and($unit->durationMinutes())->toBeNull()
        ->and($unit->prerequisiteUnitIds())->toBe([]);
});

it('rechaza titulo o descripcion invalidos', function (string $title, string $description): void {
    CourseUnit::create(
        id: curriculumUnitId(),
        code: CurriculumCode::fromString('UNI-01'),
        title: $title,
        description: $description,
        objectives: null,
        durationMinutes: null,
        position: 1,
        prerequisiteUnitIds: [],
    );
})->with([
    [' ', 'Descripción'],
    [str_repeat('A', 181), 'Descripción'],
    ['Título', ' '],
])->throws(InvalidCurriculumText::class);

it('expone el contrato publico del error de texto curricular', function (): void {
    try {
        CourseUnit::create(
            id: curriculumUnitId(),
            code: CurriculumCode::fromString('UNI-01'),
            title: '',
            description: 'Descripción',
            objectives: null,
            durationMinutes: null,
            position: 1,
            prerequisiteUnitIds: [],
        );
    } catch (InvalidCurriculumText $exception) {
        expect($exception->errorCode())->toBe('INVALID_CURRICULUM_TEXT')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidCurriculumText.');
});

it('rechaza duraciones no positivas', function (?int $duration): void {
    CourseUnit::create(
        id: curriculumUnitId(),
        code: CurriculumCode::fromString('UNI-01'),
        title: 'Título',
        description: 'Descripción',
        objectives: null,
        durationMinutes: $duration,
        position: 1,
        prerequisiteUnitIds: [],
    );
})->with([0, -1])->throws(InvalidCurriculumDuration::class);

it('expone el contrato publico del error de duracion curricular', function (): void {
    try {
        CourseUnit::create(
            id: curriculumUnitId(),
            code: CurriculumCode::fromString('UNI-01'),
            title: 'Título',
            description: 'Descripción',
            objectives: null,
            durationMinutes: 0,
            position: 1,
            prerequisiteUnitIds: [],
        );
    } catch (InvalidCurriculumDuration $exception) {
        expect($exception->errorCode())->toBe('INVALID_CURRICULUM_DURATION')
            ->and($exception->statusCode())->toBe(422);

        return;
    }

    $this->fail('Se esperaba InvalidCurriculumDuration.');
});

it('rechaza posiciones no positivas', function (int $position): void {
    CourseUnit::create(
        id: curriculumUnitId(),
        code: CurriculumCode::fromString('UNI-01'),
        title: 'Título',
        description: 'Descripción',
        objectives: null,
        durationMinutes: null,
        position: $position,
        prerequisiteUnitIds: [],
    );
})->with([0, -1])->throws(InvalidArgumentException::class);
