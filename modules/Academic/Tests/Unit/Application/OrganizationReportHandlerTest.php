<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Queries\GetOrganizationAdoptionReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationCompletionReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationParticipationReportQuery;
use Modules\Academic\Application\Queries\GetOrganizationPerformanceReportQuery;
use Modules\Academic\Application\Responses\OrganizationAdoptionReportResponse;
use Modules\Academic\Application\Responses\OrganizationCompletionReportResponse;
use Modules\Academic\Application\Responses\OrganizationParticipationReportResponse;
use Modules\Academic\Application\Responses\OrganizationPerformanceReportResponse;
use Modules\Academic\Application\Services\CourseExamAttemptsLookup;
use Modules\Academic\Application\Services\ReportOrganizationResolver;
use Modules\Academic\Application\UseCases\GetOrganizationAdoptionReportHandler;
use Modules\Academic\Application\UseCases\GetOrganizationCompletionReportHandler;
use Modules\Academic\Application\UseCases\GetOrganizationParticipationReportHandler;
use Modules\Academic\Application\UseCases\GetOrganizationPerformanceReportHandler;
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
use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

function persistedOrgReportOrganization(): Organization
{
    $organization = Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Organización de prueba '.Str::random(4)),
        type: OrganizationType::DrivingSchool,
    );
    app(OrganizationRepository::class)->save($organization);

    return $organization;
}

function persistedOrgReportUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario institucional de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedOrgReportEnrollment(
    Organization $organization,
    CourseId $courseId,
    string $userId,
    EnrollmentStatus $status = EnrollmentStatus::Active,
    ?DateTimeImmutable $enrolledAt = null,
): Enrollment {
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $courseId,
        userId: $userId,
        organizationId: $organization->id(),
        status: $status,
        source: EnrollmentSource::Institutional,
        enrolledAt: $enrolledAt,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

/** Persists a submitted exam attempt for the given course/user with the given score/competency breakdown. */
function persistedSubmittedOrgAttempt(
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
        'Pregunta de reporte institucional',
        $totalPoints,
        $correctResponse,
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    app(QuestionRepository::class)->save($question);

    $exam = Exam::create(
        id: $examId,
        courseId: $courseId,
        title: 'Examen de reporte institucional',
        questions: [ExamQuestion::create(1, $question->id(), $totalPoints)],
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
        title: 'Examen de reporte institucional',
        durationMinutes: null,
        passingScore: 50,
        shuffleQuestions: false,
        feedbackMode: ExamFeedbackMode::None,
        questions: [
            AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                1,
                $question->id(),
                CompetencyId::fromString($competencyId),
                $totalPoints,
                'Pregunta de reporte institucional',
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

it('agrega la participacion de una organizacion a partir de sus inscripciones', function (): void {
    $organization = persistedOrgReportOrganization();
    $course = createDraftCourseForPublishing('ORGP-'.strtoupper((string) Str::random(4)));
    $lessonId = app(CourseLessonCatalog::class)->lessonIdsFor($course)[0];

    $participatingUserId = persistedOrgReportUserId();
    $participatingEnrollment = persistedOrgReportEnrollment($organization, $course->id(), $participatingUserId);
    $progress = app(EnrollmentProgressRepository::class)->findByEnrollmentId($participatingEnrollment->id());
    $progress->completeLesson(LessonId::fromString($lessonId), new DateTimeImmutable, null);
    app(EnrollmentProgressRepository::class)->save($progress);

    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId());

    $handler = new GetOrganizationParticipationReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
        app(EnrollmentProgressRepository::class),
    );

    $reports = $handler->handle(new GetOrganizationParticipationReportQuery(organizationIds: [$organization->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(OrganizationParticipationReportResponse::class)
        ->and($reports[0]->enrollmentCount)->toBe(2)
        ->and($reports[0]->participatingCount)->toBe(1)
        ->and($reports[0]->participationRate)->toBe(50.0);
});

it('agrega la finalizacion de una organizacion a partir de sus inscripciones', function (): void {
    $organization = persistedOrgReportOrganization();
    $course = createDraftCourseForPublishing('ORGC-'.strtoupper((string) Str::random(4)));

    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId(), EnrollmentStatus::Completed);
    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId(), EnrollmentStatus::Active);

    $handler = new GetOrganizationCompletionReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
    );

    $reports = $handler->handle(new GetOrganizationCompletionReportQuery(organizationIds: [$organization->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(OrganizationCompletionReportResponse::class)
        ->and($reports[0]->enrollmentCount)->toBe(2)
        ->and($reports[0]->completedCount)->toBe(1)
        ->and($reports[0]->completionRate)->toBe(50.0);
});

it('agrega la adopcion mensual de una organizacion', function (): void {
    $organization = persistedOrgReportOrganization();
    $course = createDraftCourseForPublishing('ORGA-'.strtoupper((string) Str::random(4)));

    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId(), enrolledAt: new DateTimeImmutable('2026-06-15'));
    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId(), enrolledAt: new DateTimeImmutable('2026-07-01'));
    persistedOrgReportEnrollment($organization, $course->id(), persistedOrgReportUserId(), enrolledAt: new DateTimeImmutable('2026-07-20'));

    $handler = new GetOrganizationAdoptionReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
    );

    $reports = $handler->handle(new GetOrganizationAdoptionReportQuery(organizationIds: [$organization->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(OrganizationAdoptionReportResponse::class)
        ->and($reports[0]->monthlyEnrollments)->toBe([
            ['month' => '2026-06', 'count' => 1],
            ['month' => '2026-07', 'count' => 2],
        ]);
});

it('agrega el desempeno de una organizacion solo con los intentos de sus propios inscritos', function (): void {
    $organization = persistedOrgReportOrganization();
    $course = createDraftCourseForPublishing('ORGD-'.strtoupper((string) Str::random(4)));
    $competencyId = persistedQuestionCompetencyId();

    $orgUserId = persistedOrgReportUserId();
    persistedOrgReportEnrollment($organization, $course->id(), $orgUserId);
    persistedSubmittedOrgAttempt($course->id(), $orgUserId, score: 8, totalPoints: 10, passed: true, competencyId: $competencyId);

    $otherUserId = persistedOrgReportUserId();
    persistedSubmittedOrgAttempt($course->id(), $otherUserId, score: 2, totalPoints: 10, passed: false, competencyId: $competencyId);

    $handler = new GetOrganizationPerformanceReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
        new CourseExamAttemptsLookup(app(ExamRepository::class), app(ExamAttemptRepository::class)),
    );

    $reports = $handler->handle(new GetOrganizationPerformanceReportQuery(organizationIds: [$organization->id()->value()]));

    expect($reports)->toHaveCount(1)
        ->and($reports[0])->toBeInstanceOf(OrganizationPerformanceReportResponse::class)
        ->and($reports[0]->attemptCount)->toBe(1)
        ->and($reports[0]->averageScore)->toBe(8.0)
        ->and($reports[0]->passedCount)->toBe(1);
});

it('cubre todas las organizaciones cuando no se especifican organization_ids', function (): void {
    persistedOrgReportOrganization();

    $reports = (new GetOrganizationCompletionReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
    ))->handle(new GetOrganizationCompletionReportQuery);

    expect(count($reports))->toBeGreaterThanOrEqual(1);
});

it('rechaza un organization_id inexistente', function (): void {
    $handler = new GetOrganizationCompletionReportHandler(
        new ReportOrganizationResolver(app(OrganizationRepository::class)),
        app(EnrollmentRepository::class),
    );

    expect(fn () => $handler->handle(new GetOrganizationCompletionReportQuery(organizationIds: [(string) Str::uuid()])))
        ->toThrow(OrganizationNotFound::class);
});
