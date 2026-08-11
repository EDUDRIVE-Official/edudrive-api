<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

function singleChoicePayload(string $competencyId): array
{
    return [
        'competency_id' => $competencyId,
        'type' => 'single_choice',
        'prompt' => '¿Cuál es la velocidad máxima en zona urbana?',
        'score' => 1,
        'response' => ['optionId' => 'opt-a'],
        'options' => [
            ['ref_id' => 'opt-a', 'label' => '50 km/h'],
            ['ref_id' => 'opt-b', 'label' => '80 km/h'],
        ],
    ];
}

it('crea una pregunta de selección única', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/questions', singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertCreated()
        ->assertJsonPath('data.type', 'single_choice')
        ->assertJsonPath('data.correct.optionId', 'opt-a')
        ->assertJsonPath('data.score', 1)
        ->assertJsonStructure(['data' => ['id', 'type', 'competency_id', 'prompt', 'score', 'options', 'correct', 'media']]);
});

it('crea una pregunta true_false sin opciones', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/questions', [
        'competency_id' => persistedQuestionCompetencyId(),
        'type' => 'true_false',
        'prompt' => '¿El titular siempre cede el paso al peatón?',
        'score' => 2,
        'response' => ['correct' => true],
    ])->assertCreated()
        ->assertJsonPath('data.type', 'true_false')
        ->assertJsonPath('data.correct.correct', true)
        ->assertJsonPath('data.options', []);
});

it('rechaza un puntaje de cero con validación', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/questions', [
        'competency_id' => persistedQuestionCompetencyId(),
        'type' => 'single_choice',
        'prompt' => 'Puntaje inválido',
        'score' => 0,
        'response' => ['optionId' => 'opt-a'],
        'options' => [
            ['ref_id' => 'opt-a', 'label' => 'A'],
            ['ref_id' => 'opt-b', 'label' => 'B'],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['score']);
});

it('rechaza crear una pregunta con competencia inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/questions', singleChoicePayload((string) Str::uuid()))
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');
});

it('lista preguntas filtradas por competencia', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $firstCompetency = persistedQuestionCompetencyId();
    $secondCompetency = persistedQuestionCompetencyId();

    $this->postJson('/api/v1/academic/questions', singleChoicePayload($firstCompetency))->assertCreated();
    $this->postJson('/api/v1/academic/questions', singleChoicePayload($secondCompetency))->assertCreated();

    $this->getJson('/api/v1/academic/questions')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/academic/questions?competency_id='.$firstCompetency)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.competency_id', $firstCompetency);
});

it('obtiene el detalle de una pregunta con su respuesta correcta', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $created = $this->postJson('/api/v1/academic/questions', singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertCreated();

    $this->getJson('/api/v1/academic/questions/'.$created->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.correct', ['type' => 'single_choice', 'optionId' => 'opt-a']);
});

it('actualiza prompt y puntaje de una pregunta', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $created = $this->postJson('/api/v1/academic/questions', singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertCreated();

    $questionId = $created->json('data.id');

    $this->putJson("/api/v1/academic/questions/{$questionId}", [
        'type' => 'single_choice',
        'prompt' => 'Prompt actualizado',
        'score' => 5,
        'response' => ['optionId' => 'opt-a'],
        'options' => [
            ['ref_id' => 'opt-a', 'label' => '50 km/h'],
            ['ref_id' => 'opt-b', 'label' => '80 km/h'],
        ],
    ])->assertOk()
        ->assertJsonPath('data.prompt', 'Prompt actualizado')
        ->assertJsonPath('data.score', 5);
});

it('elimina una pregunta y deja de listarla', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $created = $this->postJson('/api/v1/academic/questions', singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertCreated();

    $questionId = $created->json('data.id');

    $this->deleteJson("/api/v1/academic/questions/{$questionId}")->assertNoContent();

    $this->getJson('/api/v1/academic/questions')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('responde 404 para preguntas inexistentes en obtener, actualizar y eliminar', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $questionId = (string) Str::uuid();

    $this->getJson("/api/v1/academic/questions/{$questionId}")
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');

    $this->putJson("/api/v1/academic/questions/{$questionId}", singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');

    $this->deleteJson("/api/v1/academic/questions/{$questionId}")
        ->assertNotFound()
        ->assertJsonPath('code', 'QUESTION_NOT_FOUND');
});

it('protege todos los endpoints de preguntas con autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/questions')->assertUnauthorized();
    $this->getJson('/api/v1/academic/questions/'.Str::uuid())->assertUnauthorized();
    $this->postJson('/api/v1/academic/questions', [])->assertUnauthorized();
    $this->putJson('/api/v1/academic/questions/'.Str::uuid(), [])->assertUnauthorized();
    $this->deleteJson('/api/v1/academic/questions/'.Str::uuid())->assertUnauthorized();
});

it('permite a un estudiante listar pero no crear preguntas', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/questions')->assertOk();

    $this->postJson('/api/v1/academic/questions', singleChoicePayload(persistedQuestionCompetencyId()))
        ->assertForbidden();
});

it('rechaza una URL de media no https', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/academic/questions', [
        'competency_id' => persistedQuestionCompetencyId(),
        'type' => 'situational',
        'prompt' => 'Caso práctico con media',
        'score' => 1,
        'response' => ['type' => 'single_choice', 'optionId' => 'opt-a'],
        'options' => [
            ['ref_id' => 'opt-a', 'label' => 'A'],
            ['ref_id' => 'opt-b', 'label' => 'B'],
        ],
        'media' => [
            ['type' => 'image', 'url' => 'http://example.com/caso.jpg'],
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_QUESTION');
});
