<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Modules\Academic\Application\Exceptions\ProgramCodeAlreadyExists;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\ProgramStatus;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentProgramRepository;

it('guarda y reconstruye un programa educativo completo con sus cursos ordenados', function (): void {
    $repository = app(EloquentProgramRepository::class);
    $firstCourseId = CourseId::fromString('019c4000-0000-7000-8000-000000000001');
    $secondCourseId = CourseId::fromString('019c4000-0000-7000-8000-000000000002');
    createProgramCourseRow($firstCourseId, 'COURSE-001');
    createProgramCourseRow($secondCourseId, 'COURSE-002');

    $program = EducationalProgram::create(
        id: ProgramId::fromString('019c4000-0000-7000-8000-000000000010'),
        code: ProgramCode::fromString('moto-learner-01'),
        title: 'Formacion inicial para motociclistas',
        description: 'Trayecto regional para personas que empiezan a conducir motocicleta.',
        audience: ProgramAudience::fromValues(
            minAge: 16,
            maxAge: 18,
            licenseStages: [LicenseStage::Unlicensed, LicenseStage::Learner],
            contexts: [ProgramContext::General, ProgramContext::Institutional],
            vehicleTypes: [VehicleType::Motorcycle],
        ),
    );
    $program->replaceCourses([$secondCourseId, $firstCourseId]);
    $program->publish(new DateTimeImmutable('2026-08-03T14:30:00+00:00'));

    $repository->save($program);

    $stored = $repository->findById($program->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->id()->equals($program->id()))->toBeTrue()
        ->and($stored?->code()->value())->toBe('MOTO-LEARNER-01')
        ->and($stored?->title())->toBe('Formacion inicial para motociclistas')
        ->and($stored?->description())->toBe('Trayecto regional para personas que empiezan a conducir motocicleta.')
        ->and($stored?->audience()->minAge())->toBe(16)
        ->and($stored?->audience()->maxAge())->toBe(18)
        ->and($stored?->audience()->licenseStages())->toBe([
            LicenseStage::Unlicensed,
            LicenseStage::Learner,
        ])
        ->and($stored?->audience()->contexts())->toBe([
            ProgramContext::General,
            ProgramContext::Institutional,
        ])
        ->and($stored?->audience()->vehicleTypes())->toBe([VehicleType::Motorcycle])
        ->and($stored?->status())->toBe(ProgramStatus::Published)
        ->and($stored?->publishedAt()?->format(DATE_ATOM))->toBe('2026-08-03T14:30:00+00:00')
        ->and($stored?->archivedAt())->toBeNull()
        ->and(array_map(
            static fn ($course): string => $course->courseId()->value(),
            $stored?->courses() ?? [],
        ))->toBe([$secondCourseId->value(), $firstCourseId->value()])
        ->and(array_map(
            static fn ($course): int => $course->position(),
            $stored?->courses() ?? [],
        ))->toBe([1, 2]);
});

it('consulta programas por codigo normalizado y los lista ordenados por codigo', function (): void {
    $repository = app(EloquentProgramRepository::class);

    foreach ([
        ['019c4000-0000-7000-8000-000000000021', 'ZETA-001'],
        ['019c4000-0000-7000-8000-000000000022', 'ALPHA-001'],
    ] as [$id, $code]) {
        $repository->save(EducationalProgram::create(
            id: ProgramId::fromString($id),
            code: ProgramCode::fromString($code),
            title: "Programa {$code}",
            description: 'Descripcion regional del programa.',
            audience: ProgramAudience::fromValues(null, null, [], [], []),
        ));
    }

    $found = $repository->findByCode(ProgramCode::fromString(' alpha-001 '));

    expect($repository->existsByCode(ProgramCode::fromString('alpha-001')))->toBeTrue()
        ->and($repository->existsByCode(ProgramCode::fromString('missing-001')))->toBeFalse()
        ->and($found?->code()->value())->toBe('ALPHA-001')
        ->and(array_map(
            static fn (EducationalProgram $program): string => $program->code()->value(),
            $repository->all(),
        ))->toBe(['ALPHA-001', 'ZETA-001']);
});

it('traduce la carrera del codigo unico al error publico de programa duplicado', function (): void {
    $repository = app(EloquentProgramRepository::class);
    $existing = EducationalProgram::create(
        id: ProgramId::fromString('019c4000-0000-7000-8000-000000000031'),
        code: ProgramCode::fromString('RACE-CODE-001'),
        title: 'Programa existente',
        description: 'Programa que gana la carrera de persistencia.',
        audience: ProgramAudience::fromValues(null, null, [], [], []),
    );
    $conflicting = EducationalProgram::create(
        id: ProgramId::fromString('019c4000-0000-7000-8000-000000000032'),
        code: ProgramCode::fromString('race-code-001'),
        title: 'Programa concurrente',
        description: 'Programa que pierde la carrera de persistencia.',
        audience: ProgramAudience::fromValues(null, null, [], [], []),
    );
    $repository->save($existing);

    try {
        $repository->save($conflicting);

        test()->fail('Se esperaba ProgramCodeAlreadyExists.');
    } catch (ProgramCodeAlreadyExists $exception) {
        expect($exception->statusCode())->toBe(409)
            ->and($exception->errorCode())->toBe('PROGRAM_CODE_ALREADY_EXISTS')
            ->and($exception->getMessage())->toContain('RACE-CODE-001');
    }

    expect($repository->findById($conflicting->id()))->toBeNull();
});

it('no convierte otras violaciones de persistencia en un codigo de programa duplicado', function (): void {
    $repository = app(EloquentProgramRepository::class);
    $program = EducationalProgram::create(
        id: ProgramId::fromString('019c4000-0000-7000-8000-000000000041'),
        code: ProgramCode::fromString('OTHER-CONSTRAINT-001'),
        title: 'Programa con referencia invalida',
        description: 'Comprueba que una FK conserve su excepcion original.',
        audience: ProgramAudience::fromValues(null, null, [], [], []),
    );
    $program->replaceCourses([
        CourseId::fromString('019c4000-0000-7000-8000-000000000099'),
    ]);

    expect(fn () => $repository->save($program))->toThrow(QueryException::class);
});

function createProgramCourseRow(CourseId $id, string $code): void
{
    CourseModel::query()->create([
        'id' => $id->value(),
        'code' => $code,
        'title' => "Curso {$code}",
        'description' => null,
        'status' => 'draft',
    ]);
}
