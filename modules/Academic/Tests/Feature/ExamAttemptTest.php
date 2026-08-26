<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @return list<string> */
function persistedAttemptQuestionIds(): array
{
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $ids = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $ids[] = $question->id()->value();
    }

    return $ids;
}

function persistedAttemptExamId(): string
{
    $courseId = createDraftCourseForPublishing('EXF-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen feature intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        maxAttempts: 2,
        passingScore: 70,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
    );
    app(ExamRepository::class)->save($exam);

    return $exam->id()->value();
}

function persistedTheoryAttemptExamId(bool $allowPartialCredit, bool $applyPenalties): string
{
    $courseId = createDraftCourseForPublishing('ETF-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::MultiSelect,
        $competencyId,
        'Selecciona dos opciones correctas',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            QuestionOption::create('opt-c', QuestionOptionId::fromString((string) Str::uuid()), 3, 'C'),
        ],
        sourceKind: QuestionSourceKind::Official,
        licenseCategories: [LicenseCategory::fromString('B1')],
    );
    $questionRepository->save($question);

    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen theory intento',
        [ExamQuestion::create(1, $question->id(), 10)],
        maxAttempts: 1,
        passingScore: 50,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
        allowPartialCredit: $allowPartialCredit,
        applyPenalties: $applyPenalties,
    );
    app(ExamRepository::class)->save($exam);

    return $exam->id()->value();
}

it('inicia un intento y responde sus preguntas', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])
        ->assertCreated()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonCount(2, 'data.questions');

    $attemptId = $started->json('data.id');

    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", [
        'response' => ['type' => 'single_choice', 'optionId' => 'opt-a'],
    ])->assertOk()
        ->assertJsonPath('data.questions.0.user_response.optionId', 'opt-a');
});

it('rechaza iniciar un intento sobre un examen inexistente', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => (string) Str::uuid()])
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_NOT_FOUND');
});

it('rechaza un segundo intento activo para el mismo examen y usuario', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();

    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])
        ->assertStatus(409)
        ->assertJsonPath('code', 'EXAM_ATTEMPT_LIMIT_REACHED');
});

it('envía un intento y devuelve el resultado', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/2", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-b']])->assertOk();

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.score', 20)
        ->assertJsonPath('data.grading_breakdown.0.score', 10)
        ->assertJsonPath('data.grading_breakdown.1.is_correct', true)
        ->assertJsonPath('data.competency_results.0.score', 20)
        ->assertJsonPath('data.percentage', 100)
        ->assertJsonPath('data.passed', true);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonPath('data.grading_breakdown.0.question_id', $started->json('data.questions.0.question_id'))
        ->assertJsonPath('data.competency_results.0.percentage', 100);

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertStatus(409)
        ->assertJsonPath('code', 'EXAM_ATTEMPT_ALREADY_SUBMITTED');
});

it('usa la politica del examen theory y expone study_recommendations', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedTheoryAttemptExamId(false, false);

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');

    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", [
        'response' => ['type' => 'multi_select', 'optionIds' => ['opt-a']],
    ])->assertOk();

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonPath('data.score', 0)
        ->assertJsonPath('data.study_recommendations.0.percentage', 0)
        ->assertJsonPath('data.study_recommendations.0.question_ids.0', $started->json('data.questions.0.question_id'));
});

it('no expone study_recommendations para examenes standard', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/2", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-b']])->assertOk();

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonMissingPath('data.study_recommendations');
});

it('rechaza responder o enviar un intento de otro usuario', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();
    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');

    actingAsAuthenticatedUser();

    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_ATTEMPT_NOT_FOUND');

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertNotFound()
        ->assertJsonPath('code', 'EXAM_ATTEMPT_NOT_FOUND');
});

it('oculta la retroalimentación al estudiante con feedback_mode none', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $courseId = createDraftCourseForPublishing('EXN-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen sin feedback',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        passingScore: 70,
        feedbackMode: ExamFeedbackMode::None,
    );
    app(ExamRepository::class)->save($exam);

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $exam->id()->value()])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/2", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-b']])->assertOk();
    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")->assertOk();

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonMissingPath('data.questions.0.is_correct')
        ->assertJsonMissingPath('data.questions.0.correct_response')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonMissingPath('data.grading_breakdown')
        ->assertJsonMissingPath('data.competency_results');

    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonPath('data.grading_breakdown.0.score', 10)
        ->assertJsonPath('data.competency_results.0.percentage', 100)
        ->assertJsonPath('data.questions.0.is_correct', true);
});

it('mantiene la asimetria entre submit y show cuando feedback_mode none oculta feedback al dueno', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $courseId = createDraftCourseForPublishing('EXA-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen asimetrico',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        passingScore: 70,
        feedbackMode: ExamFeedbackMode::None,
    );
    app(ExamRepository::class)->save($exam);

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $exam->id()->value()])->assertCreated();
    $attemptId = $started->json('data.id');
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/1", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-a']])->assertOk();
    $this->putJson("/api/v1/academic/exam-attempts/{$attemptId}/questions/2", ['response' => ['type' => 'single_choice', 'optionId' => 'opt-b']])->assertOk();

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.grading_breakdown.0.score', 10)
        ->assertJsonPath('data.competency_results.0.score', 20)
        ->assertJsonPath('data.questions.0.is_correct', true);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonMissingPath('data.questions.0.is_correct')
        ->assertJsonMissingPath('data.questions.0.correct_response')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonMissingPath('data.grading_breakdown')
        ->assertJsonMissingPath('data.competency_results');
});

it('oculta feedback y grading en show cuando el intento vence por timeout y queda canceled', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();
    $courseId = createDraftCourseForPublishing('EXT-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionIds = persistedAttemptQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen con timeout',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        durationMinutes: 1,
        passingScore: 70,
        feedbackMode: ExamFeedbackMode::AfterSubmission,
    );
    app(ExamRepository::class)->save($exam);

    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $exam->id()->value()])->assertCreated();
    $attemptId = $started->json('data.id');

    DB::table('academic_exam_attempts')
        ->where('id', $attemptId)
        ->update(['started_at' => '2000-01-01 00:00:00']);

    $this->postJson("/api/v1/academic/exam-attempts/{$attemptId}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'canceled');

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonMissingPath('data.questions.0.is_correct')
        ->assertJsonMissingPath('data.questions.0.correct_response')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonMissingPath('data.grading_breakdown')
        ->assertJsonMissingPath('data.competency_results');

    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonMissingPath('data.questions.0.is_correct')
        ->assertJsonMissingPath('data.questions.0.correct_response')
        ->assertJsonMissingPath('data.questions.0.explanation')
        ->assertJsonMissingPath('data.grading_breakdown')
        ->assertJsonMissingPath('data.competency_results');
});

it('permite a un docente con permiso ver intentos de terceros', function (): void {
    /** @var TestCase $this */
    $owner = actingAsAuthenticatedUser();
    $examId = persistedAttemptExamId();
    $started = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $examId])->assertCreated();
    $attemptId = $started->json('data.id');

    actingAsRole(Role::Teacher);

    $this->getJson("/api/v1/academic/exam-attempts/{$attemptId}")
        ->assertOk()
        ->assertJsonPath('data.user_id', $owner->id);

    $this->getJson('/api/v1/academic/exam-attempts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('niega a un estudiante listar intentos', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/exam-attempts')
        ->assertForbidden();
});

it('protege los endpoints de intentos con autenticación', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => (string) Str::uuid()])->assertUnauthorized();
    $this->putJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/questions/1', [])->assertUnauthorized();
    $this->postJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/submit')->assertUnauthorized();
    $this->postJson('/api/v1/academic/exam-attempts/'.Str::uuid().'/cancel')->assertUnauthorized();
    $this->getJson('/api/v1/academic/exam-attempts/'.Str::uuid())->assertUnauthorized();
    $this->getJson('/api/v1/academic/exam-attempts')->assertUnauthorized();
});
