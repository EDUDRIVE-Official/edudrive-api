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
        name: 'Formacion regional para aprendices de motocicleta',
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

it('crea un programa nuevo en borrador y sin cursos', function (): void {
    $program = createRegionalEducationalProgram();

    expect($program->id()->value())->toBe('019c4ab7-6b40-7d3e-b0aa-7c3c47ea9d12')
        ->and($program->code()->value())->toBe('MOTO-APRENDIZ')
        ->and($program->name())->toBe('Formacion regional para aprendices de motocicleta')
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
        name: 'Programa restaurado',
        description: null,
        audience: $audience,
        courses: $courses,
        status: ProgramStatus::Published,
        publishedAt: $publishedAt,
        archivedAt: null,
    );

    expect($program->name())->toBe('Programa restaurado')
        ->and($program->description())->toBeNull()
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
