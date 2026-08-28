<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Queries\GetCourseActivityReportQuery;
use Modules\Academic\Application\Queries\GetCourseApprovalReportQuery;
use Modules\Academic\Application\Queries\GetCourseCompetencyReportQuery;
use Modules\Academic\Application\Queries\GetCoursePerformanceReportQuery;
use Modules\Academic\Application\Queries\GetCourseProgressReportQuery;
use Modules\Academic\Application\Responses\CourseActivityReportResponse;
use Modules\Academic\Application\Responses\CourseApprovalReportResponse;
use Modules\Academic\Application\Responses\CourseCompetencyReportResponse;
use Modules\Academic\Application\Responses\CoursePerformanceReportResponse;
use Modules\Academic\Application\Responses\CourseProgressReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportCourseResolver;
use Modules\Academic\Application\UseCases\GetCourseActivityReportHandler;
use Modules\Academic\Application\UseCases\GetCourseApprovalReportHandler;
use Modules\Academic\Application\UseCases\GetCourseCompetencyReportHandler;
use Modules\Academic\Application\UseCases\GetCoursePerformanceReportHandler;
use Modules\Academic\Application\UseCases\GetCourseProgressReportHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

function persistedReportUserId(?DateTimeImmutable $lastLoginAt = null): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario para reportes',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    if ($lastLoginAt !== null) {
        $user->recordLogin($lastLoginAt);
    }
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedReportEnrollment(CourseId $courseId, string $userId): Enrollment
{
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $courseId,
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

/** Persists a submitted exam attempt for the given course with the given score/competency breakdown. */
function persistedReportAttempt(
    CourseId $courseId,
    string $userId,
    int $score,
    int $totalPoints,
    bool $passed,
    string $competencyId,
): ExamAttempt {
    $examId = ExamId::fromString((string) Str::uuid());
    $correctResponse = SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']);

    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        CompetencyId::fromString($competencyId),
        'Pregunta de reporte',
        $totalPoints,
        $correctResponse,
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    app(QuestionRepository::class)->save($question);
    $questionId = $question->id();

    $exam = Exam::create(
        id: $examId,
        courseId: $courseId,
        title: 'Examen de reporte',
        questions: [
            ExamQuestion::create(1, $questionId, $totalPoints),
        ],
        maxAttempts: 10,
        passingScore: 50,
    );
    app(ExamRepository::class)->save($exam);

    $attempt = ExamAttempt::restore(
        id: ExamAttemptId::fromString((string) Str::uuid()),
        examId: $examId,
        userId: $userId,
        status: ExamAttemptStatus::Submitted,
        startedAt: new DateTimeImmutable('-1 hour'),
        submittedAt: new DateTimeImmutable('now'),
        title: 'Examen de reporte',
        durationMinutes: null,
        passingScore: 50,
        shuffleQuestions: false,
        feedbackMode: ExamFeedbackMode::None,
        questions: [
            AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                1,
                $questionId,
                CompetencyId::fromString($competencyId),
                $totalPoints,
                'Pregunta de reporte',
                QuestionType::SingleChoice,
                [],
                $correctResponse,
            ),
        ],
        score: $score,
        totalPoints: $totalPoints,
        percentage: (int) round($score / $totalPoints * 100),
        passed: $passed,
        competencyBreakdown: [
            new CompetencyGrade(CompetencyId::fromString($competencyId), $score, $totalPoints, (int) round($score / $totalPoints * 100)),
        ],
    );

    app(ExamAttemptRepository::class)->save($attempt);

    return $attempt;
}

it('agrega el progreso de un curso a partir de sus inscripciones', function (): void {
    $course = createDraftCourseForPublishing('PRG-'.strtoupper((string) Str::random(4)));
    $lessonCatalog = app(CourseLessonCatalog::class);
    $lessonId = $lessonCatalog->lessonIdsFor($course)[0];

    $completedUserId = persistedReportUserId();
    $completedEnrollment = persistedReportEnrollment($course->id(), $completedUserId);
    $progress = app(EnrollmentProgressRepository::class)->findByEnrollmentId($completedEnrollment->id());
    $progress->completeLesson(LessonId::fromString($lessonId), new DateTimeImmutable, null);
    app(EnrollmentProgressRepository::class)->save($progress);

    persistedReportEnrollment($course->id(), persistedReportUserId());

    $handler = new GetCourseProgressReportHandler(
        new ReportCourseResolver(app(CourseRepository::class)),
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        $lessonCatalog,
    );

    $reports = $handler->handle(new GetCourseProgressReportQuery(courseIds: [$course->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(CourseProgressReportResponse::class)
        ->and($reports[0]->enrollmentCount)->toBe(2)
        ->and($reports[0]->fullyCompletedCount)->toBe(1)
        ->and($reports[0]->averageCompletionPercentage)->toBe(50.0);
});

it('agrega el rendimiento y la aprobacion de un curso a partir de sus intentos enviados', function (): void {
    $course = createDraftCourseForPublishing('PRF-'.strtoupper((string) Str::random(4)));
    $competencyId = persistedQuestionCompetencyId();

    persistedReportAttempt($course->id(), persistedReportUserId(), score: 8, totalPoints: 10, passed: true, competencyId: $competencyId);
    persistedReportAttempt($course->id(), persistedReportUserId(), score: 4, totalPoints: 10, passed: false, competencyId: $competencyId);

    $lookup = new CourseExamAttemptsLookup(app(ExamRepository::class), app(ExamAttemptRepository::class));
    $courseResolver = new ReportCourseResolver(app(CourseRepository::class));

    $performance = (new GetCoursePerformanceReportHandler($courseResolver, $lookup))
        ->handle(new GetCoursePerformanceReportQuery(courseIds: [$course->id()->value()]));

    expect($performance)->toHaveCount(1)
        ->and($performance[0])->toBeInstanceOf(CoursePerformanceReportResponse::class)
        ->and($performance[0]->attemptCount)->toBe(2)
        ->and($performance[0]->averageScore)->toBe(6.0)
        ->and($performance[0]->averagePercentage)->toBe(60.0);

    $approval = (new GetCourseApprovalReportHandler($courseResolver, $lookup))
        ->handle(new GetCourseApprovalReportQuery(courseIds: [$course->id()->value()]));

    expect($approval)->toHaveCount(1)
        ->and($approval[0])->toBeInstanceOf(CourseApprovalReportResponse::class)
        ->and($approval[0]->attemptCount)->toBe(2)
        ->and($approval[0]->passedCount)->toBe(1)
        ->and($approval[0]->passRate)->toBe(50.0);
});

it('agrega el desempeno por competencia de un curso', function (): void {
    $course = createDraftCourseForPublishing('CMP-'.strtoupper((string) Str::random(4)));
    $competencyId = persistedQuestionCompetencyId();

    persistedReportAttempt($course->id(), persistedReportUserId(), score: 10, totalPoints: 10, passed: true, competencyId: $competencyId);
    persistedReportAttempt($course->id(), persistedReportUserId(), score: 6, totalPoints: 10, passed: true, competencyId: $competencyId);

    $handler = new GetCourseCompetencyReportHandler(
        new ReportCourseResolver(app(CourseRepository::class)),
        new CourseExamAttemptsLookup(app(ExamRepository::class), app(ExamAttemptRepository::class)),
        app(CompetencyRepository::class),
    );

    $reports = $handler->handle(new GetCourseCompetencyReportQuery(courseIds: [$course->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(CourseCompetencyReportResponse::class)
        ->and($reports[0]->competencies)->toHaveCount(1)
        ->and($reports[0]->competencies[0]['competency_id'])->toBe($competencyId)
        ->and($reports[0]->competencies[0]['sample_count'])->toBe(2)
        ->and($reports[0]->competencies[0]['average_percentage'])->toBe(80.0);
});

it('agrega la actividad de un curso a partir del ultimo inicio de sesion de sus inscritos', function (): void {
    $course = createDraftCourseForPublishing('ACT-'.strtoupper((string) Str::random(4)));

    $activeUserId = persistedReportUserId(new DateTimeImmutable('-5 days'));
    persistedReportEnrollment($course->id(), $activeUserId);

    $inactiveUserId = persistedReportUserId(new DateTimeImmutable('-90 days'));
    persistedReportEnrollment($course->id(), $inactiveUserId);

    $neverLoggedInUserId = persistedReportUserId();
    persistedReportEnrollment($course->id(), $neverLoggedInUserId);

    $handler = new GetCourseActivityReportHandler(
        new ReportCourseResolver(app(CourseRepository::class)),
        app(EnrollmentRepository::class),
        app(UserRepository::class),
    );

    $reports = $handler->handle(new GetCourseActivityReportQuery(courseIds: [$course->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(CourseActivityReportResponse::class)
        ->and($reports[0]->enrollmentCount)->toBe(3)
        ->and($reports[0]->activeLast30DaysCount)->toBe(1)
        ->and($reports[0]->neverLoggedInCount)->toBe(1);
});

it('cubre todos los cursos cuando no se especifican course_ids', function (): void {
    createDraftCourseForPublishing('ALL-'.strtoupper((string) Str::random(4)));

    $reports = (new GetCourseProgressReportHandler(
        new ReportCourseResolver(app(CourseRepository::class)),
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
        app(CourseLessonCatalog::class),
    ))->handle(new GetCourseProgressReportQuery);

    expect(count($reports))->toBeGreaterThanOrEqual(1);
});
