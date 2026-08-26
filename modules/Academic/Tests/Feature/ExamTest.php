<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @return list<array{question_id: string, points: int}> */
function examPayloadQuestions(array $questionIds): array
{
    return array_map(static fn (string $id): array => ['question_id' => $id, 'points' => 1], $questionIds);
}

function examPayload(string $courseId, array $questionIds, array $overrides = []): array
{
    return array_merge([
        'course_id' => $courseId,
        'title' => 'Examen teórico',
        'description' => 'Evaluación final.',
        'duration_minutes' => 45,
        'max_attempts' => 2,
        'passing_score' => 70,
        'shuffle_questions' => true,
        'feedback_mode' => 'after_submission',
        'questions' => examPayloadQuestions($questionIds),
    ], $overrides);
}

function theoryExamPayload(string $courseId, array $questionIds, array $overrides = []): array
{
    return array_merge(examPayload($courseId, $questionIds), [
        'kind' => 'theory',
        'license_category' => 'b1',
        'allow_partial_credit' => true,
        'apply_penalties' => true,
    ], $overrides);
}

function persistedExamCourseId(): string
{
    return createDraftCourseForPublishing('EXM-'.strtoupper((string) Str::random(4)))->id()->value();
}

/** @return list<string> */
function persistedExamQuestionIds(): array
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

/** @return list<string> */
function persistedOfficialExamQuestionIds(array $categories = ['B1']): array
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

it('crea un examen con preguntas válidas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated()
        ->assertJsonPath('data.title', 'Examen teórico')
        ->assertJsonPath('data.max_attempts', 2)
        ->assertJsonPath('data.passing_score', 70)
        ->assertJsonPath('data.shuffle_questions', true)
        ->assertJsonPath('data.feedback_mode', 'after_submission')
        ->assertJsonCount(2, 'data.questions')
        ->assertJsonStructure(['data' => ['id', 'title', 'course_id', 'questions']]);
});

it('crea un examen theory con metadata y reglas de grading', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(
        persistedExamCourseId(),
        persistedOfficialExamQuestionIds(['B1', 'A2B']),
    ))
        ->assertCreated()
        ->assertJsonPath('data.kind', 'theory')
        ->assertJsonPath('data.license_category', 'B1')
        ->assertJsonPath('data.allow_partial_credit', true)
        ->assertJsonPath('data.apply_penalties', true);
});

it('rechaza crear un examen con curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload((string) Str::uuid(), persistedExamQuestionIds()))
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza crear un examen con pregunta inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), [(string) Str::uuid()]))
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');
});

it('valida duración, intentos y puntaje fuera de rango', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $questionIds = persistedExamQuestionIds();

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['duration_minutes' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['duration_minutes']);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['max_attempts' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['max_attempts']);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds, ['passing_score' => 101]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['passing_score']);
});

it('valida kind y license_category para examenes theory', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $questionIds = persistedExamQuestionIds();

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(
        persistedExamCourseId(),
        $questionIds,
        ['kind' => 'invalid-kind'],
    ))->assertUnprocessable()
        ->assertJsonValidationErrors(['kind']);

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(
        persistedExamCourseId(),
        $questionIds,
        ['license_category' => '   '],
    ))->assertUnprocessable()
        ->assertJsonValidationErrors(['license_category']);
});

it('rechaza un examen sin preguntas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), [], ['questions' => []]))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_EXAM');
});

it('rechaza un examen sin la clave questions', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $payload = examPayload(persistedExamCourseId(), persistedExamQuestionIds());
    unset($payload['questions']);

    $this->postJson('/api/v1/academic/exams', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_EXAM');
});

it('rechaza preguntas duplicadas en un examen', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $courseId = persistedExamCourseId();
    $questionIds = persistedExamQuestionIds();

    $payload = examPayload($courseId, [$questionIds[0], $questionIds[0]]);

    $this->postJson('/api/v1/academic/exams', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_EXAM');
});

it('rechaza un examen theory con preguntas custom', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_THEORY_EXAM');
});

it('rechaza un examen theory con preguntas oficiales fuera de categoria', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(
        persistedExamCourseId(),
        persistedOfficialExamQuestionIds(['A1']),
    ))->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_THEORY_EXAM');
});

it('permite un examen theory con preguntas oficiales compatibles con la categoria', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/exams', theoryExamPayload(
        persistedExamCourseId(),
        persistedOfficialExamQuestionIds(['B1', 'A2B']),
    ))->assertCreated()
        ->assertJsonPath('data.kind', 'theory')
        ->assertJsonPath('data.license_category', 'B1');
});

it('lista exámenes filtrados por curso', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $courseId = persistedExamCourseId();
    $questionIds = persistedExamQuestionIds();

    $this->postJson('/api/v1/academic/exams', examPayload($courseId, $questionIds))->assertCreated();
    $this->postJson('/api/v1/academic/exams', examPayload($courseId, $questionIds))->assertCreated();
    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), $questionIds))->assertCreated();

    $this->getJson('/api/v1/academic/exams')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/v1/academic/exams?course_id='.$courseId)
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('obtiene el detalle de un examen con sus preguntas en orden', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $this->getJson('/api/v1/academic/exams/'.$created->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.questions.0.position', 1)
        ->assertJsonPath('data.questions.1.position', 2)
        ->assertJsonPath('data.questions.0.type', 'single_choice')
        ->assertJsonPath('data.kind', 'standard')
        ->assertJsonPath('data.license_category', null)
        ->assertJsonPath('data.allow_partial_credit', false)
        ->assertJsonPath('data.apply_penalties', false);
});

it('actualiza un examen', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $officialQuestionIds = persistedOfficialExamQuestionIds(['A2B', 'B1']);

    $this->putJson('/api/v1/academic/exams/'.$created->json('data.id'), [
        'title' => 'Examen actualizado',
        'max_attempts' => 3,
        'passing_score' => 80,
        'kind' => 'theory',
        'license_category' => 'A2B',
        'allow_partial_credit' => true,
        'apply_penalties' => true,
        'questions' => examPayloadQuestions($officialQuestionIds),
    ])->assertOk()
        ->assertJsonPath('data.title', 'Examen actualizado')
        ->assertJsonPath('data.max_attempts', 3)
        ->assertJsonPath('data.passing_score', 80)
        ->assertJsonPath('data.kind', 'theory')
        ->assertJsonPath('data.license_category', 'A2B')
        ->assertJsonPath('data.allow_partial_credit', true)
        ->assertJsonPath('data.apply_penalties', true);
});

it('elimina un examen y deja de listarlo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $created = $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertCreated();

    $this->deleteJson('/api/v1/academic/exams/'.$created->json('data.id'))->assertNoContent();

    $this->getJson('/api/v1/academic/exams')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('responde 404 para exámenes inexistentes en obtener, actualizar y eliminar', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $examId = (string) Str::uuid();

    $this->getJson("/api/v1/academic/exams/{$examId}")->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
    $this->putJson("/api/v1/academic/exams/{$examId}", ['title' => 'X', 'questions' => examPayloadQuestions(persistedExamQuestionIds())])
        ->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
    $this->deleteJson("/api/v1/academic/exams/{$examId}")->assertNotFound()->assertJsonPath('code', 'EXAM_NOT_FOUND');
});

it('protege los endpoints de exámenes con autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/exams')->assertUnauthorized();
    $this->getJson('/api/v1/academic/exams/'.Str::uuid())->assertUnauthorized();
    $this->postJson('/api/v1/academic/exams', [])->assertUnauthorized();
    $this->putJson('/api/v1/academic/exams/'.Str::uuid(), [])->assertUnauthorized();
    $this->deleteJson('/api/v1/academic/exams/'.Str::uuid())->assertUnauthorized();
});

it('permite a un estudiante listar pero no crear exámenes', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/exams')->assertOk();
    $this->postJson('/api/v1/academic/exams', examPayload(persistedExamCourseId(), persistedExamQuestionIds()))
        ->assertForbidden();
});
