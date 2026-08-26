<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Exceptions\EnrollmentNotFound;
use Modules\Academic\Application\Exceptions\LessonNotFound;
use Modules\Academic\Application\Exceptions\UnitLocked;
use Modules\Academic\Application\Responses\EnrollmentProgressResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Exceptions\InvalidEnrollment;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

uses(RefreshDatabase::class);

final class SpyLearningEventRecorder implements LearningEventRecorder
{
    /** @var list<LearningEventEntry> */
    public array $recorded = [];

    public function record(LearningEventEntry $entry): void
    {
        $this->recorded[] = $entry;
    }
}

function completeLessonHandler(?SpyLearningEventRecorder $recorder = null): CompleteLessonHandler
{
    return new CompleteLessonHandler(
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
        new EnrollmentProgressCalculator(
            app(CourseRepository::class),
            new CourseLessonCatalog(app(UnitContentRepository::class)),
            app(ExamRepository::class),
            app(ExamAttemptRepository::class),
        ),
        $recorder ?? new SpyLearningEventRecorder,
    );
}

function persistedTaskSevenUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    app(UserRepository::class)->save($user);

    return $user->id();
}

function activeEnrollmentForLessonCompletion(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CL-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedTaskSevenUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

it('completa una leccion del curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    $response = completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: 7,
    ));

    expect($response)->toBeInstanceOf(EnrollmentProgressResponse::class)
        ->and($response->completedLessonsCount)->toBe(1)
        ->and($response->timeSpentMinutes)->toBe(7);
});

it('rechaza completar una leccion de un enrollment inexistente o ajeno', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: (string) Str::uuid(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: persistedTaskSevenUserId(),
        timeSpentMinutes: null,
    )))->toThrow(EnrollmentNotFound::class);
});

it('rechaza completar una leccion si el enrollment no esta activo', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $enrollment->cancel();
    app(EnrollmentRepository::class)->save($enrollment);
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(InvalidEnrollment::class);
});

it('rechaza una leccion que no pertenece al curso de la inscripcion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: (string) Str::uuid(),
        userId: $enrollment->userId(),
        timeSpentMinutes: null,
    )))->toThrow(LessonNotFound::class);
});

it('rechaza completar una leccion de una unidad bloqueada por prerrequisitos', function (): void {
    $module1Id = CourseModuleId::fromString((string) Str::uuid());
    $unit1Id = CourseUnitId::fromString((string) Str::uuid());
    $module2Id = CourseModuleId::fromString((string) Str::uuid());
    $unit2Id = CourseUnitId::fromString((string) Str::uuid());

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-CL-GATE-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso con prerrequisitos'),
    );
    $course->replaceCurriculum([
        CourseModule::create(
            id: $module1Id,
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Modulo 1',
            description: 'Primer modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                CourseUnit::create(
                    id: $unit1Id,
                    code: CurriculumCode::fromString('UNI-01'),
                    title: 'Unidad 1',
                    description: 'Primera unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
        CourseModule::create(
            id: $module2Id,
            code: CurriculumCode::fromString('MOD-02'),
            title: 'Modulo 2',
            description: 'Segundo modulo.',
            objectives: null,
            durationMinutes: 30,
            position: 2,
            prerequisiteModuleIds: [$module1Id],
            units: [
                CourseUnit::create(
                    id: $unit2Id,
                    code: CurriculumCode::fromString('UNI-02'),
                    title: 'Unidad 2',
                    description: 'Segunda unidad.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
    ]);
    app(CourseRepository::class)->save($course);

    $lesson1Id = LessonId::fromString((string) Str::uuid());
    app(UnitContentRepository::class)->replaceAtomically($course->id(), $unit1Id, UnitContent::create($unit1Id, [
        Lesson::create($lesson1Id, CurriculumCode::fromString('LEC-01'), 'Leccion 1', null, 10, 1, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido.']),
        ]),
    ]));

    $lesson2Id = LessonId::fromString((string) Str::uuid());
    app(UnitContentRepository::class)->replaceAtomically($course->id(), $unit2Id, UnitContent::create($unit2Id, [
        Lesson::create($lesson2Id, CurriculumCode::fromString('LEC-02'), 'Leccion 2', null, 10, 1, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido.']),
        ]),
    ]));

    $userId = persistedTaskSevenUserId();
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    expect(fn () => completeLessonHandler()->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lesson2Id->value(),
        userId: $userId,
        timeSpentMinutes: null,
    )))->toThrow(UnitLocked::class);
});

it('registra un evento de aprendizaje al completar una leccion', function (): void {
    $enrollment = activeEnrollmentForLessonCompletion();
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = (new CourseLessonCatalog(app(UnitContentRepository::class)))->lessonIdsFor($course)[0];
    $recorder = new SpyLearningEventRecorder;

    completeLessonHandler($recorder)->handle(new CompleteLessonCommand(
        enrollmentId: $enrollment->id()->value(),
        lessonId: $lessonId,
        userId: $enrollment->userId(),
        timeSpentMinutes: 9,
    ));

    expect($recorder->recorded)->toHaveCount(1)
        ->and($recorder->recorded[0]->enrollmentId)->toBe($enrollment->id()->value())
        ->and($recorder->recorded[0]->userId)->toBe($enrollment->userId())
        ->and($recorder->recorded[0]->courseId)->toBe($enrollment->courseId()->value())
        ->and($recorder->recorded[0]->verb)->toBe(LearningVerb::LessonCompleted)
        ->and($recorder->recorded[0]->subjectId)->toBe($lessonId)
        ->and($recorder->recorded[0]->evidence)->toBe(['time_spent_minutes' => 9]);
});
