<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Authorization\Domain\Enums\Role;

it('shows a personal summary before answering and restores the exact next step', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    $this->get('/mi-perfil')->assertOk()->assertSeeText('Mis habilidades en esta práctica')->assertSeeText('Por empezar')->assertSeeText('Por practicar');
    expect(DB::table('academic_descubro_progress')->count())->toBe(0);
    crossingAction('start')->assertRedirect();
    crossingAction('lesson-next')->assertRedirect();
    $this->get('/mi-perfil')->assertOk()->assertSeeText('Aprendo · Paso 2 de 3')->assertSeeText('Continuar donde quedé');
    $this->get('/mi-pasaporte-vial')->assertOk()->assertSeeText('Aprendo · Paso 2 de 3')->assertSeeText('Por practicar');
    $before = app(DescubroPracticeProgress::class)->find((string) $user->id);
    $this->get('/mi-perfil')->assertOk();
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id))->toBe($before)
        ->and(DB::table('road_passport_evidence')->count())->toBe(0);
});

it('shows the same partial skill summary in the profile and passport without granting evidence', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    crossingAction('answer', 1)->assertRedirect();
    crossingAction('next')->assertRedirect();
    crossingAction('answer', 1)->assertRedirect();
    foreach (['/mi-perfil', '/mi-pasaporte-vial', '/descubro/cruzar-acompanado?page=home'] as $url) {
        $response = $this->get($url)->assertOk()->assertSeeText('Practicado en pantalla')->assertSeeText('En práctica')->assertSeeText('Por practicar');
        $response->assertSee('data-competency="EDU-PED-001.E1.S2"', false)->assertSeeText('La observación con una persona adulta en un circuito protegido sigue pendiente.');
    }
    expect(DB::table('road_passport_evidence')->count())->toBe(0)
        ->and(DB::table('academic_enrollment_lesson_completions')->count())->toBe(0);
});
