<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumDuration;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumText;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;

function curriculumModuleId(string $value = '01981a64-8300-7b1d-b442-764ea7f915d0'): CourseModuleId
{
    return CourseModuleId::fromString($value);
}

function curriculumModuleUnit(): CourseUnit
{
    return CourseUnit::create(
        id: CourseUnitId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CurriculumCode::fromString('UNI-01'),
        title: 'Señalización',
        description: 'Descripción de unidad',
        objectives: null,
        durationMinutes: 20,
        position: 1,
        prerequisiteUnitIds: [],
    );
}

it('normaliza el identificador de modulo y compara por valor', function (): void {
    $id = CourseModuleId::fromString(' 01981A64-8300-7B1D-B442-764EA7F915D0 ');

    expect($id->value())
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915d0')
        ->and((string) $id)
        ->toBe('01981a64-8300-7b1d-b442-764ea7f915d0')
        ->and($id->equals(curriculumModuleId()))
        ->toBeTrue();
});

it('rechaza identificadores de modulo que no son uuid', function (): void {
    CourseModuleId::fromString('module-1');
})->throws(InvalidArgumentException::class);

it('crea un modulo curricular normalizado con unidades', function (): void {
    $prerequisite = curriculumModuleId('01981a64-8300-7b1d-b442-764ea7f915d1');
    $unit = curriculumModuleUnit();

    $module = CourseModule::create(
        id: curriculumModuleId(),
        code: CurriculumCode::fromString(' mod-01 '),
        title: '  Fundamentos viales  ',
        description: '  Conceptos esenciales de seguridad vial.  ',
        objectives: '  Comprender el entorno vial.  ',
        durationMinutes: 90,
        position: 2,
        prerequisiteModuleIds: [$prerequisite],
        units: [$unit],
    );

    expect($module->id()->equals(curriculumModuleId()))
        ->toBeTrue()
        ->and($module->code()->value())
        ->toBe('MOD-01')
        ->and($module->title())
        ->toBe('Fundamentos viales')
        ->and($module->description())
        ->toBe('Conceptos esenciales de seguridad vial.')
        ->and($module->objectives())
        ->toBe('Comprender el entorno vial.')
        ->and($module->durationMinutes())
        ->toBe(90)
        ->and($module->position())
        ->toBe(2)
        ->and($module->prerequisiteModuleIds())
        ->toBe([$prerequisite])
        ->and($module->units())
        ->toBe([$unit]);
});

it('convierte objetivos vacios en null y admite duracion ausente', function (): void {
    $module = CourseModule::create(
        id: curriculumModuleId(),
        code: CurriculumCode::fromString('MOD-01'),
        title: 'Fundamentos',
        description: 'Descripción',
        objectives: ' ',
        durationMinutes: null,
        position: 1,
        prerequisiteModuleIds: [],
        units: [],
    );

    expect($module->objectives())->toBeNull()
        ->and($module->durationMinutes())->toBeNull()
        ->and($module->prerequisiteModuleIds())->toBe([])
        ->and($module->units())->toBe([]);
});

it('rechaza titulo o descripcion invalidos en un modulo', function (string $title, string $description): void {
    CourseModule::create(
        id: curriculumModuleId(),
        code: CurriculumCode::fromString('MOD-01'),
        title: $title,
        description: $description,
        objectives: null,
        durationMinutes: null,
        position: 1,
        prerequisiteModuleIds: [],
        units: [],
    );
})->with([
    [' ', 'Descripción'],
    [str_repeat('A', 181), 'Descripción'],
    ['Título', ' '],
])->throws(InvalidCurriculumText::class);

it('rechaza duraciones no positivas en un modulo', function (int $duration): void {
    CourseModule::create(
        id: curriculumModuleId(),
        code: CurriculumCode::fromString('MOD-01'),
        title: 'Título',
        description: 'Descripción',
        objectives: null,
        durationMinutes: $duration,
        position: 1,
        prerequisiteModuleIds: [],
        units: [],
    );
})->with([0, -1])->throws(InvalidCurriculumDuration::class);

it('rechaza posiciones no positivas en un modulo', function (int $position): void {
    CourseModule::create(
        id: curriculumModuleId(),
        code: CurriculumCode::fromString('MOD-01'),
        title: 'Título',
        description: 'Descripción',
        objectives: null,
        durationMinutes: null,
        position: $position,
        prerequisiteModuleIds: [],
        units: [],
    );
})->with([0, -1])->throws(InvalidArgumentException::class);
