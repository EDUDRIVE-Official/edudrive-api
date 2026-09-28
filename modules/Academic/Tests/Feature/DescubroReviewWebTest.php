<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Authorization\Domain\Enums\Role;

it('reviews all explanations without starting a practice or creating evidence', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    foreach (['Camino por la acera', 'Me detengo y comprobamos', 'Cruzamos juntos'] as $index => $title) {
        $this->get('/descubro/cruzar-acompanado?page=review&lesson='.$index)->assertOk()
            ->assertSeeText($title)->assertSeeText('Explicación '.($index + 1).' de 3')
            ->assertSeeText('Escuchar esta pantalla')->assertSee('data-dc-read', false)
            ->assertSee('href="/descubro/cruzar-acompanado"', false)
            ->assertDontSee('name="action"', false);
    }
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id))->toBeNull();
    expect(DB::table('road_passport_evidence')->count())->toBe(0);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Empezar juntos');
});

it('review preserves feedback attempts revision and completed progress', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    crossingAction('answer', 0)->assertRedirect();
    $before = DB::table('academic_descubro_progress')->where('user_id', $user->id)->first();
    $this->get('/descubro/cruzar-acompanado?page=review&lesson=1')->assertOk();
    expect(DB::table('academic_descubro_progress')->where('user_id', $user->id)->first())->toEqual($before);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Hacemos una pausa.');
    crossingAction('retry')->assertRedirect();
    foreach ([1, 1, 1, 0] as $choice) {
        crossingAction('answer', $choice)->assertRedirect();
        crossingAction('next')->assertRedirect();
    }
    $before = DB::table('academic_descubro_progress')->where('user_id', $user->id)->first();
    $this->get('/descubro/cruzar-acompanado?page=review&lesson=2')->assertOk();
    expect(DB::table('academic_descubro_progress')->where('user_id', $user->id)->first())->toEqual($before);
    $this->get('/descubro/cruzar-acompanado')->assertSeeText('Práctica digital completada')
        ->assertSeeText('Repasar las tres explicaciones');
    expect(DB::table('road_passport_evidence')->count())->toBe(0);
});

it('review rejects malformed explanation selectors and stays internal', function (): void {
    $this->get('/descubro/cruzar-acompanado?page=review')->assertRedirect(route('login'));
    $this->actingAs(actingAsRole(Role::Student), 'web');
    foreach (['lesson[]=1', 'lesson=-1', 'lesson=3', 'lesson=anything'] as $query) {
        $this->get('/descubro/cruzar-acompanado?page=review&'.$query)->assertNotFound();
    }
    app()->instance('env', 'production');
    $this->get('/descubro/cruzar-acompanado?page=review')->assertNotFound();
});
