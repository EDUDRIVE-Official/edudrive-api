<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController as Crossing;
use Modules\Authorization\Domain\Enums\Role;

function crossingAction(string $action, ?int $choice = null): TestResponse
{
    $run = app(DescubroPracticeProgress::class)->find((string) auth('web')->id()) ?? Crossing::initial();

    return test()->post(route('descubro.crossing.update'), array_filter([
        'action' => $action, 'revision' => $run['revision'], 'choice' => $choice,
    ], static fn ($value) => $value !== null));
}

function crossingBegin(): void
{
    foreach (['start', 'lesson-next', 'lesson-next', 'practice-start'] as $action) {
        crossingAction($action)->assertRedirect(route('descubro.crossing.show', [], false));
    }
}

it('requires authentication for the internal experience and its writes', function (): void {
    $this->get('/descubro/cruzar-acompanado')->assertRedirect(route('login'));
    $this->post('/descubro/cruzar-acompanado', [])->assertRedirect(route('login'));
});

it('is unavailable in production including direct posts', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web');
    app()->instance('env', 'production');
    $this->get('/descubro/cruzar-acompanado')->assertNotFound();
    $this->withSession(['_token' => 'descubro-test-token'])->post('/descubro/cruzar-acompanado', ['action' => 'start', 'revision' => 0, '_token' => 'descubro-test-token'])->assertNotFound();
    $this->get('/mi-perfil')->assertOk()->assertDontSee('Abrir el recorrido');
});

it('links the learner profile and shows durable formative practice in the passport', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web');
    $this->get('/mi-perfil')->assertOk()->assertSee(route('descubro.crossing.show'));
    $this->get('/descubro/cruzar-acompanado')->assertOk()->assertSeeText('Empezar juntos');
    crossingBegin();
    foreach ([1, 1, 1, 0] as $index => $choice) {
        if ($index === 3) {
            $this->get('/descubro/cruzar-acompanado')->assertOk()
                ->assertSeeText('El acompañante ya comprobó el entorno y dio la indicación.')
                ->assertDontSeeText('falta comprobar antes de cruzar');
        }
        crossingAction('answer', $choice)->assertRedirect();
        crossingAction('next')->assertRedirect();
    }
    $this->get('/descubro/cruzar-acompanado')->assertOk()->assertSeeText('Práctica digital completada');
    $this->get('/mi-pasaporte-vial')->assertOk()->assertSeeText('Práctica digital completada')
        ->assertSeeText('Habilidad en desarrollo')->assertSeeText('pendiente de observación')
        ->assertSeeText('Avance guardado en tu cuenta');
    expect(DB::table('road_passport_evidence')->count())->toBe(0)
        ->and(DB::table('road_passports')->count())->toBe(0)
        ->and(DB::table('academic_enrollment_lesson_completions')->count())->toBe(0);
});

it('rejects skipped steps incorrect answers and duplicate revisions', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingAction('practice-start')->assertStatus(422);
    crossingBegin();
    crossingAction('next')->assertStatus(422);
    crossingAction('answer', 2)->assertStatus(422);
    crossingAction('answer', 0)->assertRedirect();
    crossingAction('next')->assertStatus(422);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Hacemos una pausa.');
    crossingAction('retry')->assertRedirect();
    $run = app(DescubroPracticeProgress::class)->find((string) $user->id);
    crossingAction('answer', 1)->assertRedirect();
    $this->post('/descubro/cruzar-acompanado', ['action' => 'answer', 'choice' => 1, 'revision' => $run['revision']])->assertStatus(409);
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id)['attempts'])->toHaveCount(2);
});

it('keeps progress through navigation and isolates users sharing a browser session', function (): void {
    $first = actingAsRole(Role::Student);
    $second = actingAsRole(Role::Student);
    $this->actingAs($first, 'web');
    crossingBegin();
    crossingAction('answer', 1)->assertRedirect();
    crossingAction('next')->assertRedirect();
    $this->get('/descubro/cruzar-acompanado?page=adult')->assertOk();
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Viene un bus.');
    $this->actingAs($second, 'web');
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Empezar juntos');
    $this->get('/mi-pasaporte-vial')->assertDontSeeText('Práctica digital en curso');
    $this->actingAs($first, 'web');
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Viene un bus.');
});

it('does not allow a result page to manufacture completion', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web');
    $this->get('/descubro/cruzar-acompanado?page=result')->assertOk()->assertSeeText('Empezar juntos')->assertDontSeeText('Práctica digital completada');
    $this->get('/descubro/cruzar-acompanado?page[]=practice')->assertNotFound();
});

it('retains a completed practice when another practice is started', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web');
    crossingBegin();
    foreach ([1, 1, 1, 0] as $choice) {
        crossingAction('answer', $choice)->assertRedirect();
        crossingAction('next')->assertRedirect();
    }
    crossingAction('practice-start')->assertRedirect();
    $this->get('/mi-pasaporte-vial')->assertSeeText('Práctica digital completada')->assertSeeText('Habilidad en desarrollo');
});

it('returns a root relative location after saving a decision', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    $response = crossingAction('answer', 1);
    $response->assertStatus(302)->assertHeader('Location', '/descubro/cruzar-acompanado');
    $this->get($response->headers->get('Location'))->assertOk()->assertSeeText('Lo practicamos juntos.');
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id)['feedback'])->toBe('success');
});
