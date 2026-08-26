<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
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

function theoryExamFeaturePayloadQuestions(array $questionIds): array
{
    return array_map(static fn (string $id): array => ['question_id' => $id, 'points' => 1], $questionIds);
}

function theoryExamFeatureExamPayload(string $courseId, array $questionIds, array $overrides = []): array
{
    return array_merge([
        'course_id' => $courseId,
        'title' => 'Examen estandar',
        'description' => 'Evaluacion final.',
        'duration_minutes' => 45,
        'max_attempts' => 2,
        'passing_score' => 70,
        'shuffle_questions' => true,
        'feedback_mode' => 'after_submission',
        'questions' => theoryExamFeaturePayloadQuestions($questionIds),
    ], $overrides);
}

function theoryExamFeatureTheoryPayload(string $courseId, array $questionIds, array $overrides = []): array
{
    return array_merge(theoryExamFeatureExamPayload($courseId, $questionIds), [
        'kind' => 'theory',
        'license_category' => 'b1',
        'allow_partial_credit' => false,
        'apply_penalties' => false,
    ], $overrides);
}

function theoryExamFeatureQuestionIds(): array
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

function theoryExamFeatureOfficialQuestionIds(array $categories = ['B1']): array
{
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $ids = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta oficial '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
            sourceKind: QuestionSourceKind::Official,
            licenseCategories: array_map(
                static fn (string $category): LicenseCategory => LicenseCategory::fromString($category),
                $categories,
            ),
        );
        $questionRepository->save($question);
        $ids[] = $question->id()->value();
    }

    return $ids;
}

function theoryExamFeatureCourseId(): string
{
    return createDraftCourseForPublishing('THF-'.strtoupper((string) Str::random(4)))->id()->value();
}

function theoryExamFeatureExamId(bool $allowPartialCredit, bool $applyPenalties): string
{
    $courseId = theoryExamFeatureCourseId();
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

function theoryExamFeatureStandardAttemptExamId(): string
{
    $courseId = theoryExamFeatureCourseId();
    $questionIds = theoryExamFeatureQuestionIds();
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen standard intento',
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

it('lista solo examenes theory y obtiene su detalle', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', theoryExamFeatureExamPayload(theoryExamFeatureCourseId(), theoryExamFeatureQuestionIds()))->assertCreated();
    $theory = $this->postJson('/api/v1/academic/exams', theoryExamFeatureTheoryPayload(
        theoryExamFeatureCourseId(),
        theoryExamFeatureOfficialQuestionIds(['B1']),
    ))->assertCreated();

    $this->getJson('/api/v1/academic/theory-exams')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.kind', 'theory');

    $this->getJson('/api/v1/academic/theory-exams/'.$theory->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.kind', 'theory')
        ->assertJsonPath('data.license_category', 'B1');
});

it('inicia simulacion teorica solo sobre examenes theory', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $standardExam = $this->postJson(
        '/api/v1/academic/exams',
        theoryExamFeatureExamPayload(theoryExamFeatureCourseId(), theoryExamFeatureQuestionIds()),
    )->assertCreated();

    $theoryExam = $this->postJson('/api/v1/academic/exams', theoryExamFeatureTheoryPayload(
        theoryExamFeatureCourseId(),
        theoryExamFeatureOfficialQuestionIds(['B1']),
    ))->assertCreated();

    actingAsAuthenticatedUser();

    $this->postJson('/api/v1/academic/theory-exams/'.$theoryExam->json('data.id').'/start')
        ->assertCreated()
        ->assertJsonPath('data.exam_id', $theoryExam->json('data.id'));

    $this->postJson('/api/v1/academic/theory-exams/'.$standardExam->json('data.id').'/start')
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_THEORY_EXAM');
});

it('expone historial teorico por usuario y categoria', function (): void {
    /** @var TestCase $this */
    actingAsAuthenticatedUser();

    $theoryExamId = theoryExamFeatureExamId(false, false);
    $standardExamId = theoryExamFeatureStandardAttemptExamId();

    $theory = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $theoryExamId])->assertCreated();
    $this->putJson('/api/v1/academic/exam-attempts/'.$theory->json('data.id').'/questions/1', [
        'response' => ['type' => 'multi_select', 'optionIds' => ['opt-a']],
    ])->assertOk();
    $this->postJson('/api/v1/academic/exam-attempts/'.$theory->json('data.id').'/submit')->assertOk();

    $standard = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $standardExamId])->assertCreated();
    $this->putJson('/api/v1/academic/exam-attempts/'.$standard->json('data.id').'/questions/1', [
        'response' => ['type' => 'single_choice', 'optionId' => 'opt-a'],
    ])->assertOk();
    $this->putJson('/api/v1/academic/exam-attempts/'.$standard->json('data.id').'/questions/2', [
        'response' => ['type' => 'single_choice', 'optionId' => 'opt-b'],
    ])->assertOk();
    $this->postJson('/api/v1/academic/exam-attempts/'.$standard->json('data.id').'/submit')->assertOk();

    $this->getJson('/api/v1/academic/theory-attempts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.license_category', 'B1');

    $this->getJson('/api/v1/academic/theory-attempts?license_category=B1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('permite a un docente consultar historial teorico de otro usuario', function (): void {
    /** @var TestCase $this */
    $owner = actingAsAuthenticatedUser();

    $theoryExamId = theoryExamFeatureExamId(false, false);
    $theory = $this->postJson('/api/v1/academic/exam-attempts', ['exam_id' => $theoryExamId])->assertCreated();
    $this->putJson('/api/v1/academic/exam-attempts/'.$theory->json('data.id').'/questions/1', [
        'response' => ['type' => 'multi_select', 'optionIds' => ['opt-a']],
    ])->assertOk();
    $this->postJson('/api/v1/academic/exam-attempts/'.$theory->json('data.id').'/submit')->assertOk();

    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/theory-attempts?user_id='.$owner->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $owner->id);
});
