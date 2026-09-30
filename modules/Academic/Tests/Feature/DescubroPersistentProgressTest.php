<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress as Progress;
use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController as Crossing;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Application\Services\StudentLearningResetService;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('resumes the same decision and feedback after logout and a fresh login', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    crossingAction('answer', 1)->assertRedirect();
    crossingAction('next')->assertRedirect();
    crossingAction('answer', 0)->assertRedirect();
    $before = app(Progress::class)->find((string) $user->id);
    $this->post('/logout')->assertRedirect();
    $this->flushSession();
    $this->actingAs($user, 'web');
    $this->get('/descubro/cruzar-acompanado')->assertOk()->assertSeeText('Viene un bus.')
        ->assertSeeText('Hacemos una pausa.')->assertSeeText('Volver a intentar');
    expect(app(Progress::class)->find((string) $user->id))->toBe($before);
    $this->get('/mi-pasaporte-vial')->assertOk()->assertSeeText('Práctica digital en curso')->assertSeeText('Avance guardado en tu cuenta');
    crossingAction('retry')->assertRedirect();
    crossingAction('answer', 1)->assertRedirect();
    expect(app(Progress::class)->find((string) $user->id)['revision'])->toBe($before['revision'] + 2);
});

it('keeps a completed practice after the session expires without awarding mastery', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    foreach ([1, 1, 1, 0] as $choice) {
        crossingAction('answer', $choice)->assertRedirect();
        crossingAction('next')->assertRedirect();
    }
    $completedAt = DB::table('academic_descubro_progress')->where('user_id', $user->id)->value('completed_at');
    $this->flushSession();
    $this->actingAs($user, 'web');
    $this->get('/mi-pasaporte-vial')->assertSeeText('Práctica digital completada')->assertSeeText('Habilidad en desarrollo');
    crossingAction('practice-start')->assertRedirect();
    expect(DB::table('academic_descubro_progress')->where('user_id', $user->id)->value('completed_at'))->toBe($completedAt)
        ->and(DB::table('road_passport_evidence')->count())->toBe(0)
        ->and(DB::table('academic_enrollment_lesson_completions')->count())->toBe(0);
});

it('migrates only the authenticated legacy session once and durable progress wins', function (): void {
    $user = actingAsRole(Role::Student);
    $other = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    $legacy = array_replace(Crossing::initial(), ['phase' => 'learn', 'lesson' => 1, 'revision' => 2]);
    $key = Crossing::key((string) $user->id);
    $this->withSession([$key => $legacy, Crossing::key((string) $other->id) => $legacy]);
    $this->get('/descubro/cruzar-acompanado')->assertOk()->assertSeeText('Paso 2 de 3');
    expect(session()->has($key))->toBeFalse()
        ->and(app(Progress::class)->find((string) $other->id))->toBeNull();
    crossingAction('lesson-next')->assertRedirect();
    $this->withSession([$key => $legacy]);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Paso 3 de 3');
    expect(DB::table('academic_descubro_progress')->count())->toBe(1);
});

it('rejects both initial and subsequent stale writers without losing state', function (): void {
    $user = actingAsRole(Role::Student);
    $store = app(Progress::class);
    $first = array_replace(Crossing::initial(), ['phase' => 'learn']);
    $store->save((string) $user->id, $first, 0);
    foreach ([0, 1] as $staleRevision) {
        if ($staleRevision === 1) {
            $newer = $store->find((string) $user->id);
            $newer['lesson'] = 1;
            $store->save((string) $user->id, $newer, 1);
        }
        $before = $store->find((string) $user->id);
        try {
            $store->save((string) $user->id, $first, $staleRevision);
            test()->fail('A stale write was accepted.');
        } catch (HttpException $exception) {
            expect($exception->getStatusCode())->toBe(409);
        }
        expect($store->find((string) $user->id))->toBe($before);
    }
    expect(DB::table('academic_descubro_progress')->count())->toBe(1);
});

it('ignores client supplied state owner and completion flags', function (): void {
    $user = actingAsRole(Role::Student);
    $other = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    $this->post('/descubro/cruzar-acompanado', ['action' => 'start', 'revision' => 0,
        'user_id' => $other->id, 'state' => ['phase' => 'result'], 'completed_once' => true])->assertRedirect();
    expect(app(Progress::class)->find((string) $other->id))->toBeNull()
        ->and(app(Progress::class)->find((string) $user->id)['phase'])->toBe('learn')
        ->and(app(Progress::class)->find((string) $user->id)['completed_once'])->toBeFalse();
});

it('does not persist an invalid transition or a malformed legacy session', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingAction('answer', 1)->assertStatus(422);
    $this->withSession([Crossing::key((string) $user->id) => ['phase' => 'result']]);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Empezar juntos');
    expect(DB::table('academic_descubro_progress')->count())->toBe(0);
});

it('includes durable practice in the existing reset archive', function (): void {
    $user = actingAsRole(Role::Student);
    $actor = actingAsRole(Role::SuperAdmin);
    $this->actingAs($user, 'web');
    crossingBegin();
    $legacy = app(Progress::class)->find((string) $user->id);
    $reset = app(StudentLearningResetService::class)->reset((string) $user->id, (string) $actor->id, 'Reinicio de prueba autorizado');
    expect($reset['summary']['academic_descubro_progress'])->toBe(1)
        ->and(app(Progress::class)->find((string) $user->id))->toBeNull();
    $this->withSession([Crossing::key((string) $user->id) => $legacy]);
    $this->get('/descubro/cruzar-acompanado')->assertOk()->assertSeeText('Empezar juntos');
    expect(app(Progress::class)->find((string) $user->id))->toBeNull();
    crossingAction('start')->assertRedirect();
    expect(app(Progress::class)->find((string) $user->id)['phase'])->toBe('learn');
});

it('does not mix content versions and deletes progress when its account is deleted', function (): void {
    $user = actingAsRole(Role::Student);
    app(Progress::class)->save((string) $user->id, array_replace(Crossing::initial(), ['phase' => 'learn']), 0);
    DB::table('academic_descubro_progress')->where('user_id', $user->id)->update(['content_version' => 'future']);
    expect(app(Progress::class)->find((string) $user->id))->toBeNull();
    DB::table('users')->where('id', $user->id)->delete();
    expect(DB::table('academic_descubro_progress')->count())->toBe(0);
});
