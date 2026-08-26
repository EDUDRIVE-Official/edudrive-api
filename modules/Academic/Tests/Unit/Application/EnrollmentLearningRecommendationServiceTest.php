<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\EnrollmentLearningRecommendationService;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
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
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\Services\CourseCurriculumUnlockCalculator;
use Modules\Academic\Domain\Services\CourseLessonCatalog;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
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

function persistedRecommendationUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de recomendaciones',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function enrollmentForRecommendations(?Course $course = null): Enrollment
{
    $course ??= createDraftCourseForPublishing('PRG-REC-'.strtoupper((string) Str::random(4)));
    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: persistedRecommendationUserId(),
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}

function recommendationService(): EnrollmentLearningRecommendationService
{
    return new EnrollmentLearningRecommendationService(
        app(CourseRepository::class),
        new CourseLessonCatalog(app(UnitContentRepository::class)),
        new CourseCurriculumUnlockCalculator(app(UnitContentRepository::class)),
        app(ExamRepository::class),
        app(ExamAttemptRepository::class),
    );
}

/** Persists a single-choice exam tied to a competency, with a configurable maxAttempts. */
function persistedRecommendationExam(string $courseId, string $competencyId, int $maxAttempts = 1): string
{
    $questionRepository = app(QuestionRepository::class);
    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        CompetencyId::fromString($competencyId),
        '¿Pregunta de recomendacion?',
        10,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'a']),
        [
            QuestionOption::create('a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    $questionRepository->save($question);

    $exam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: CourseId::fromString($courseId),
        title: 'Examen de recomendacion',
        questions: [ExamQuestion::create(1, $question->id(), 10)],
        maxAttempts: $maxAttempts,
        passingScore: 70,
    );
    app(ExamRepository::class)->save($exam);

    return $exam->id()->value();
}

/** Starts, answers (correct or not) and submits an attempt; returns the attempt id. */
function submitRecommendationAttempt(string $examId, string $userId, bool $correct): string
{
    $commandBus = app(CommandBus::class);
    $started = $commandBus->dispatch(new StartExamAttemptCommand(examId: $examId, userId: $userId));
    assert($started instanceof ExamAttemptResponse);

    $commandBus->dispatch(new AnswerAttemptQuestionCommand(
        attemptId: $started->id,
        userId: $userId,
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $correct ? 'a' : 'b']),
    ));

    $commandBus->dispatch(new SubmitExamAttemptCommand(attemptId: $started->id, userId: $userId));

    return $started->id;
}

it('no recomienda nada para un enrollment sin actividad', function (): void {
    $enrollment = enrollmentForRecommendations();
    $progress = EnrollmentProgress::create($enrollment->id());

    $response = recommendationService()->build($enrollment, $progress);

    expect($response->enrollmentId)->toBe($enrollment->id()->value())
        ->and($response->nextLessonId)->not->toBeNull()
        ->and($response->weakCompetencies)->toBe([])
        ->and($response->retryableExams)->toBe([]);
});

it('recomienda la proxima leccion desbloqueada y no completada, y null cuando ya se completo todo', function (): void {
    $course = createDraftCourseForPublishing('PRG-REC-'.strtoupper((string) Str::random(4)));
    $catalog = new CourseLessonCatalog(app(UnitContentRepository::class));
    $onlyLessonId = $catalog->lessonIdsFor($course)[0];

    $enrollment = enrollmentForRecommendations($course);
    $progress = EnrollmentProgress::create($enrollment->id());

    $response = recommendationService()->build($enrollment, $progress);
    expect($response->nextLessonId)->toBe($onlyLessonId);

    $progress->completeLesson(LessonId::fromString($onlyLessonId), new DateTimeImmutable('now'), 5);
    $response = recommendationService()->build($enrollment, $progress);
    expect($response->nextLessonId)->toBeNull();
});

it('salta la primera leccion completada y ofrece la siguiente de la misma unidad', function (): void {
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('PRG-REC-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso con dos lecciones'),
    );
    $unitId = CourseUnitId::fromString((string) Str::uuid());
    $course->replaceCurriculum([
        CourseModule::create(
            id: CourseModuleId::fromString((string) Str::uuid()),
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Modulo 1',
            description: 'Modulo con dos lecciones.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                CourseUnit::create(
                    id: $unitId,
                    code: CurriculumCode::fromString('UNI-01'),
                    title: 'Unidad 1',
                    description: 'Unidad con dos lecciones.',
                    objectives: null,
                    durationMinutes: 20,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
    ]);
    app(CourseRepository::class)->save($course);

    $lesson1Id = LessonId::fromString((string) Str::uuid());
    $lesson2Id = LessonId::fromString((string) Str::uuid());
    app(UnitContentRepository::class)->replaceAtomically($course->id(), $unitId, UnitContent::create($unitId, [
        Lesson::create($lesson1Id, CurriculumCode::fromString('LEC-01'), 'Leccion 1', null, 10, 1, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido 1.']),
        ]),
        Lesson::create($lesson2Id, CurriculumCode::fromString('LEC-02'), 'Leccion 2', null, 10, 2, [
            ContentBlockFactory::create(ContentBlockId::fromString((string) Str::uuid()), 'text', 1, ['markdown' => 'Contenido 2.']),
        ]),
    ]));

    $enrollment = enrollmentForRecommendations($course);
    $progress = EnrollmentProgress::create($enrollment->id());
    $progress->completeLesson($lesson1Id, new DateTimeImmutable('now'), 5);

    $response = recommendationService()->build($enrollment, $progress);

    expect($response->nextLessonId)->toBe($lesson2Id->value());
});

it('usa el intento mas reciente por examen y omite competencias con desempeno perfecto', function (): void {
    $course = createDraftCourseForPublishing('PRG-REC-'.strtoupper((string) Str::random(4)));
    $enrollment = enrollmentForRecommendations($course);
    $userId = $enrollment->userId();

    $competencyA = persistedQuestionCompetencyId();
    $examA = persistedRecommendationExam($course->id()->value(), $competencyA, maxAttempts: 2);
    submitRecommendationAttempt($examA, $userId, correct: false);
    submitRecommendationAttempt($examA, $userId, correct: true);

    $competencyB = persistedQuestionCompetencyId();
    $examB = persistedRecommendationExam($course->id()->value(), $competencyB, maxAttempts: 1);
    submitRecommendationAttempt($examB, $userId, correct: false);

    $progress = EnrollmentProgress::create($enrollment->id());
    $response = recommendationService()->build($enrollment, $progress);

    $competencyIds = array_map(
        static fn ($recommendation): string => $recommendation->competencyId,
        $response->weakCompetencies,
    );

    expect($competencyIds)->toBe([$competencyB]);
});

it('recomienda reintentar examenes reprobados con intentos disponibles y excluye aprobados, agotados o en curso', function (): void {
    $course = createDraftCourseForPublishing('PRG-REC-'.strtoupper((string) Str::random(4)));
    $enrollment = enrollmentForRecommendations($course);
    $userId = $enrollment->userId();

    $examRetryable = persistedRecommendationExam($course->id()->value(), persistedQuestionCompetencyId(), maxAttempts: 2);
    submitRecommendationAttempt($examRetryable, $userId, correct: false);

    $examExhausted = persistedRecommendationExam($course->id()->value(), persistedQuestionCompetencyId(), maxAttempts: 1);
    submitRecommendationAttempt($examExhausted, $userId, correct: false);

    $examPassed = persistedRecommendationExam($course->id()->value(), persistedQuestionCompetencyId(), maxAttempts: 2);
    submitRecommendationAttempt($examPassed, $userId, correct: true);

    $examWithActiveAttempt = persistedRecommendationExam($course->id()->value(), persistedQuestionCompetencyId(), maxAttempts: 2);
    submitRecommendationAttempt($examWithActiveAttempt, $userId, correct: false);
    app(CommandBus::class)->dispatch(new StartExamAttemptCommand(examId: $examWithActiveAttempt, userId: $userId));

    $progress = EnrollmentProgress::create($enrollment->id());
    $response = recommendationService()->build($enrollment, $progress);

    $retryableExamIds = array_map(
        static fn ($exam): string => $exam->examId,
        $response->retryableExams,
    );

    expect($retryableExamIds)->toBe([$examRetryable]);
});

it('agrega competencias debiles de varios examenes ordenadas de peor a mejor desempeno', function (): void {
    $course = createDraftCourseForPublishing('PRG-REC-'.strtoupper((string) Str::random(4)));
    $enrollment = enrollmentForRecommendations($course);
    $userId = $enrollment->userId();

    $worstCompetency = persistedQuestionCompetencyId();
    $worstExam = persistedRecommendationExam($course->id()->value(), $worstCompetency, maxAttempts: 1);
    submitRecommendationAttempt($worstExam, $userId, correct: false);

    $betterCompetency = persistedQuestionCompetencyId();
    $questionRepository = app(QuestionRepository::class);
    $multiSelectQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::MultiSelect,
        CompetencyId::fromString($betterCompetency),
        'Selecciona las opciones correctas',
        10,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['a', 'b']]),
        [
            QuestionOption::create('a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            QuestionOption::create('c', QuestionOptionId::fromString((string) Str::uuid()), 3, 'C'),
        ],
    );
    $questionRepository->save($multiSelectQuestion);
    $betterExam = Exam::create(
        id: ExamId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        title: 'Examen parcial',
        questions: [ExamQuestion::create(1, $multiSelectQuestion->id(), 10)],
        maxAttempts: 1,
        passingScore: 40,
        allowPartialCredit: true,
    );
    app(ExamRepository::class)->save($betterExam);

    $commandBus = app(CommandBus::class);
    $started = $commandBus->dispatch(new StartExamAttemptCommand(examId: $betterExam->id()->value(), userId: $userId));
    assert($started instanceof ExamAttemptResponse);
    $commandBus->dispatch(new AnswerAttemptQuestionCommand(
        attemptId: $started->id,
        userId: $userId,
        position: 1,
        response: MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['a']]),
    ));
    $commandBus->dispatch(new SubmitExamAttemptCommand(attemptId: $started->id, userId: $userId));

    $progress = EnrollmentProgress::create($enrollment->id());
    $response = recommendationService()->build($enrollment, $progress);

    $competencyIds = array_map(
        static fn ($recommendation): string => $recommendation->competencyId,
        $response->weakCompetencies,
    );

    expect($competencyIds)->toBe([$worstCompetency, $betterCompetency]);
});
