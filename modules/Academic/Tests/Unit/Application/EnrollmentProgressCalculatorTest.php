<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\EnrollmentProgressCalculator;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedCalculatorUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de progreso',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function enrollmentForCalculator(): Enrollment
{
    $course = createDraftCourseForPublishing('PRG-CALC-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedCalculatorUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

function submitExamAttemptFor(Enrollment $enrollment): void
{
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        '¿Pregunta de progreso?',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'a']),
        [
            QuestionOption::create('a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    app(QuestionRepository::class)->save($question);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: $enrollment->courseId(),
        title: 'Examen de progreso',
        questions: [ExamQuestion::create(1, $question->id(), 10)],
    );
    app(ExamRepository::class)->save($exam);

    $commandBus = app(CommandBus::class);
    $attempt = $commandBus->dispatch(new StartExamAttemptCommand(examId: $exam->id()->value(), userId: $enrollment->userId()));
    assert($attempt instanceof ExamAttemptResponse);

    $commandBus->dispatch(new AnswerAttemptQuestionCommand(
        attemptId: $attempt->id,
        userId: $enrollment->userId(),
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'a']),
    ));

    $commandBus->dispatch(new SubmitExamAttemptCommand(attemptId: $attempt->id, userId: $enrollment->userId()));
}

function progressCalculator(): EnrollmentProgressCalculator
{
    return new EnrollmentProgressCalculator(
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        app(ExamRepository::class),
        app(ExamAttemptRepository::class),
    );
}

it('calcula 0% sin lecciones completadas', function (): void {
    $enrollment = enrollmentForCalculator();
    $progress = EnrollmentProgress::create($enrollment->id());

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->totalLessons)->toBe(1)
        ->and($response->completedLessonsCount)->toBe(0)
        ->and($response->progressPercentage)->toBe(0)
        ->and($response->timeSpentMinutes)->toBe(0)
        ->and($response->evaluationsCompleted)->toBe(0)
        ->and($response->lastActivityAt)->toBeNull();
});

it('calcula 100% al completar la unica leccion del curso', function (): void {
    $enrollment = enrollmentForCalculator();
    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));
    $course = app(CourseRepository::class)->findById($enrollment->courseId());
    $lessonId = $catalog->lessonIdsFor($course)[0];

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString($lessonId), new DateTimeImmutable('2026-08-15T10:00:00+00:00'), 9);

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->progressPercentage)->toBe(100)
        ->and($response->timeSpentMinutes)->toBe(9)
        ->and($response->lastActivityAt)->toBe('2026-08-15T10:00:00+00:00');
});

it('cuenta evaluaciones enviadas del curso y las usa como ultima actividad si son mas recientes', function (): void {
    $enrollment = enrollmentForCalculator();
    $before = new DateTimeImmutable('now');
    submitExamAttemptFor($enrollment);
    $after = new DateTimeImmutable('now');

    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson(LessonId::fromString((string) Str::uuid()), new DateTimeImmutable('2020-01-01T00:00:00+00:00'), 1);

    $response = progressCalculator()->calculate($enrollment, $progress);

    expect($response->evaluationsCompleted)->toBe(1);

    $lastActivityAt = new DateTimeImmutable((string) $response->lastActivityAt);
    expect($lastActivityAt->getTimestamp())->toBeGreaterThanOrEqual($before->getTimestamp())
        ->and($lastActivityAt->getTimestamp())->toBeLessThanOrEqual($after->getTimestamp());
});
