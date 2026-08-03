<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Entities\ProgramCourse;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\ProgramStatus;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\Exceptions\ArchivedProgramCannotBeModified;
use Modules\Academic\Domain\Exceptions\ProgramRequiresCourses;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

function createRegionalEducationalProgram(): EducationalProgram
{
    return EducationalProgram::create(
        id: ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        code: ProgramCode::fromString(' moto-aprendiz '),
        title: 'Formacion regional para aprendices de motocicleta',
        description: 'Trayecto formativo regional.',
        audience: ProgramAudience::fromValues(
            minAge: 16,
            maxAge: 18,
            licenseStages: [LicenseStage::Learner],
            contexts: [ProgramContext::General],
            vehicleTypes: [VehicleType::Motorcycle],
        ),
    );
}

function firstProgramCourseId(): CourseId
{
    return CourseId::fromString('019c4ab8-1c4e-7c92-8108-aead0a40a124');
}

function secondProgramCourseId(): CourseId
{
    return CourseId::fromString('019c4ab8-6cb6-7497-ada2-c70d75cab5c6');
}

/** @param list<ProgramCourse> $courses */
function restoreEducationalProgramWith(array $courses, string $title = 'Programa restaurado', string $description = 'Descripcion restaurada'): EducationalProgram
{
    return EducationalProgram::restore(
        ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        ProgramCode::fromString('REGIONAL-BASE'),
        $title,
        $description,
        ProgramAudience::fromValues(null, null, [], [], []),
        $courses,
        ProgramStatus::Draft,
        null,
        null,
    );
}

it('normaliza la identidad y el codigo del programa', function (): void {
    $id = ProgramId::fromString(' 019C4AB7-6B40-7D3E-B0AA-7C3C47EA9D12 ');
    $code = ProgramCode::fromString(' moto-aprendiz ');

    expect($id->value())->toBe('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12')
        ->and($code->value())->toBe('MOTO-APRENDIZ');
});

it('rechaza una identidad de programa invalida', function (): void {
    ProgramId::fromString('programa-invalido');
})->throws(InvalidArgumentException::class, 'El identificador del programa debe ser un UUID valido.');

it('rechaza un codigo de programa mayor a sesenta caracteres', function (): void {
    ProgramCode::fromString(str_repeat('A', 61));
})->throws(InvalidArgumentException::class, 'El codigo del programa debe tener entre 1 y 60 caracteres.');

it('acepta el parametro title y normaliza los textos al crear', function (): void {
    $program = EducationalProgram::create(
        id: ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        code: ProgramCode::fromString('REGIONAL-BASE'),
        title: '  Programa regional  ',
        description: '  Descripcion regional  ',
        audience: ProgramAudience::fromValues(null, null, [], [], []),
    );

    expect($program->title())->toBe('Programa regional')
        ->and($program->description())->toBe('Descripcion regional');
});

it('rechaza un titulo en blanco al crear', function (): void {
    EducationalProgram::create(
        ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        ProgramCode::fromString('REGIONAL-BASE'),
        '   ',
        'Descripcion regional',
        ProgramAudience::fromValues(null, null, [], [], []),
    );
})->throws(InvalidArgumentException::class, 'El titulo del programa no puede estar vacio.');

it('rechaza un titulo mayor a ciento ochenta caracteres al crear', function (): void {
    EducationalProgram::create(
        ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        ProgramCode::fromString('REGIONAL-BASE'),
        str_repeat('A', 181),
        'Descripcion regional',
        ProgramAudience::fromValues(null, null, [], [], []),
    );
})->throws(InvalidArgumentException::class, 'El titulo del programa no puede superar 180 caracteres.');

it('rechaza una descripcion en blanco al crear', function (): void {
    EducationalProgram::create(
        ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        ProgramCode::fromString('REGIONAL-BASE'),
        'Programa regional',
        '   ',
        ProgramAudience::fromValues(null, null, [], [], []),
    );
})->throws(InvalidArgumentException::class, 'La descripcion del programa no puede estar vacia.');

it('normaliza los textos al restaurar', function (): void {
    $program = restoreEducationalProgramWith([], '  Programa restaurado  ', '  Descripcion restaurada  ');

    expect($program->title())->toBe('Programa restaurado')
        ->and($program->description())->toBe('Descripcion restaurada');
});

it('rechaza textos invalidos al restaurar', function (string $title, string $description, string $message): void {
    try {
        restoreEducationalProgramWith([], $title, $description);

        test()->fail('Se esperaba el rechazo del texto invalido.');
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toBe($message);
    }
})->with([
    'titulo en blanco' => ['   ', 'Descripcion valida', 'El titulo del programa no puede estar vacio.'],
    'titulo demasiado largo' => [str_repeat('A', 181), 'Descripcion valida', 'El titulo del programa no puede superar 180 caracteres.'],
    'descripcion en blanco' => ['Titulo valido', '   ', 'La descripcion del programa no puede estar vacia.'],
]);

it('crea un programa nuevo en borrador y sin cursos', function (): void {
    $program = createRegionalEducationalProgram();

    expect($program->id()->value())->toBe('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12')
        ->and($program->code()->value())->toBe('MOTO-APRENDIZ')
        ->and($program->title())->toBe('Formacion regional para aprendices de motocicleta')
        ->and($program->description())->toBe('Trayecto formativo regional.')
        ->and($program->audience()->minAge())->toBe(16)
        ->and($program->status())->toBe(ProgramStatus::Draft)
        ->and($program->courses())->toBeEmpty()
        ->and($program->publishedAt())->toBeNull()
        ->and($program->archivedAt())->toBeNull();
});

it('restaura todos los datos de un programa', function (): void {
    $publishedAt = new DateTimeImmutable('2026-08-03 08:00:00');
    $audience = ProgramAudience::fromValues(null, null, [], [], []);
    $courses = [ProgramCourse::create(firstProgramCourseId(), 1)];

    $program = EducationalProgram::restore(
        id: ProgramId::fromString('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12'),
        code: ProgramCode::fromString('REGIONAL-BASE'),
        title: 'Programa restaurado',
        description: 'Descripcion restaurada',
        audience: $audience,
        courses: $courses,
        status: ProgramStatus::Published,
        publishedAt: $publishedAt,
        archivedAt: null,
    );

    expect($program->title())->toBe('Programa restaurado')
        ->and($program->description())->toBe('Descripcion restaurada')
        ->and($program->audience())->toBe($audience)
        ->and($program->courses())->toBe($courses)
        ->and($program->status())->toBe(ProgramStatus::Published)
        ->and($program->publishedAt())->toBe($publishedAt);
});

it('reemplaza los cursos conservando el orden recibido y posiciones desde uno', function (): void {
    $program = createRegionalEducationalProgram();

    $program->replaceCourses([secondProgramCourseId(), firstProgramCourseId()]);

    expect($program->courses())->toHaveCount(2)
        ->and($program->courses()[0]->courseId()->equals(secondProgramCourseId()))->toBeTrue()
        ->and($program->courses()[0]->position())->toBe(1)
        ->and($program->courses()[1]->courseId()->equals(firstProgramCourseId()))->toBeTrue()
        ->and($program->courses()[1]->position())->toBe(2);
});

it('rechaza cursos duplicados antes de alterar la secuencia existente', function (): void {
    $program = createRegionalEducationalProgram();
    $program->replaceCourses([firstProgramCourseId()]);

    try {
        $program->replaceCourses([secondProgramCourseId(), secondProgramCourseId()]);

        test()->fail('Se esperaba el rechazo del curso duplicado.');
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toBe('Un curso no puede aparecer mas de una vez en el programa.')
            ->and($program->courses())->toHaveCount(1)
            ->and($program->courses()[0]->courseId()->equals(firstProgramCourseId()))->toBeTrue()
            ->and($program->courses()[0]->position())->toBe(1);
    }
});

it('rechaza cursos duplicados al restaurar', function (): void {
    restoreEducationalProgramWith([
        ProgramCourse::create(firstProgramCourseId(), 1),
        ProgramCourse::create(firstProgramCourseId(), 2),
    ]);
})->throws(InvalidArgumentException::class, 'Un curso no puede aparecer mas de una vez en el programa.');

it('rechaza posiciones repetidas al restaurar', function (): void {
    restoreEducationalProgramWith([
        ProgramCourse::create(firstProgramCourseId(), 1),
        ProgramCourse::create(secondProgramCourseId(), 1),
    ]);
})->throws(InvalidArgumentException::class, 'La secuencia de cursos debe tener posiciones consecutivas desde uno.');

it('rechaza huecos entre posiciones al restaurar', function (): void {
    restoreEducationalProgramWith([
        ProgramCourse::create(firstProgramCourseId(), 1),
        ProgramCourse::create(secondProgramCourseId(), 3),
    ]);
})->throws(InvalidArgumentException::class, 'La secuencia de cursos debe tener posiciones consecutivas desde uno.');

it('rechaza una posicion que no coincide con su indice al restaurar', function (): void {
    restoreEducationalProgramWith([
        ProgramCourse::create(firstProgramCourseId(), 2),
    ]);
})->throws(InvalidArgumentException::class, 'La secuencia de cursos debe tener posiciones consecutivas desde uno.');

it('impide publicar un programa sin cursos', function (): void {
    createRegionalEducationalProgram()->publish(new DateTimeImmutable('2026-08-03 09:00:00'));
})->throws(ProgramRequiresCourses::class, 'El programa requiere al menos un curso para ser publicado.');

it('expone errores publicos coherentes para las reglas del programa', function (): void {
    $requiresCourses = ProgramRequiresCourses::create();
    $archived = ArchivedProgramCannotBeModified::create();

    expect($requiresCourses->errorCode())->toBe('PROGRAM_REQUIRES_COURSES')
        ->and($requiresCourses->statusCode())->toBe(422)
        ->and($archived->errorCode())->toBe('ARCHIVED_PROGRAM_CANNOT_BE_MODIFIED')
        ->and($archived->statusCode())->toBe(422);
});

it('publica un programa con cursos y registra la fecha', function (): void {
    $program = createRegionalEducationalProgram();
    $program->replaceCourses([firstProgramCourseId()]);
    $publishedAt = new DateTimeImmutable('2026-08-03 09:00:00');

    $program->publish($publishedAt);

    expect($program->status())->toBe(ProgramStatus::Published)
        ->and($program->publishedAt())->toBe($publishedAt);
});

it('impide cambiar la audiencia de un programa archivado', function (): void {
    $program = createRegionalEducationalProgram();
    $archivedAt = new DateTimeImmutable('2026-08-03 10:00:00');
    $program->archive($archivedAt);

    expect($program->status())->toBe(ProgramStatus::Archived)
        ->and($program->archivedAt())->toBe($archivedAt);

    $program->changeAudience(ProgramAudience::fromValues(null, null, [], [], []));
})->throws(ArchivedProgramCannotBeModified::class, 'Un programa archivado no puede ser modificado.');

it('impide reemplazar los cursos de un programa archivado', function (): void {
    $program = createRegionalEducationalProgram();
    $program->archive(new DateTimeImmutable('2026-08-03 10:00:00'));

    $program->replaceCourses([firstProgramCourseId()]);
})->throws(ArchivedProgramCannotBeModified::class, 'Un programa archivado no puede ser modificado.');
