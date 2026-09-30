<?php

declare(strict_types=1);

use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController as Crossing;
use Modules\Authorization\Domain\Enums\Role;

it('limits production review to active global super administrators and can disable it', function (): void {
    $admin = actingAsRole(Role::SuperAdmin);
    $admin->forceFill(['status' => 'active'])->save();
    $this->actingAs($admin, 'web');
    app()->instance('env', 'production');
    config(['descubro.production_review_enabled' => false]);
    $this->get('/descubro/cruzar-acompanado')->assertNotFound();
    config(['descubro.production_review_enabled' => true]);
    $this->get('/mi-perfil')->assertOk()->assertSee(route('descubro.crossing.show'));
    $this->get('/descubro/cruzar-acompanado?page=review')->assertOk()->assertSeeText('Vista de revisión');
    $this->withSession(['_token' => 'review-token'])->post('/descubro/cruzar-acompanado', [
        '_token' => 'review-token', 'action' => 'start', 'revision' => 0,
    ])->assertRedirect('/descubro/cruzar-acompanado');
    expect(app(DescubroPracticeProgress::class)->find((string) $admin->id)['phase'])->toBe('learn');
    $admin->status = 'inactive';
    expect(Crossing::enabled())->toBeFalse();
    $admin->status = 'active';
    config(['descubro.production_review_enabled' => false]);
    $this->get('/descubro/cruzar-acompanado')->assertNotFound();
});

it('hides production review from students teachers and institutional administrators including direct posts', function (): void {
    app()->instance('env', 'production');
    config(['descubro.production_review_enabled' => true, 'descubro.staging_enabled' => true]);
    foreach ([Role::Student, Role::Teacher, Role::InstitutionalAdmin] as $role) {
        $user = actingAsRole($role);
        $user->forceFill(['status' => 'active'])->save();
        $this->actingAs($user, 'web');
        $this->get('/descubro/cruzar-acompanado?page=review')->assertNotFound();
        $this->withSession(['_token' => 'review-token'])->post('/descubro/cruzar-acompanado', [
            '_token' => 'review-token', 'action' => 'start', 'revision' => 0,
        ])->assertNotFound();
        $this->get('/mi-perfil')->assertOk()->assertDontSee(route('descubro.crossing.show'));
        $this->get('/mi-pasaporte-vial')->assertOk()->assertDontSee('Volver a DESCUBRO');
        expect(app(DescubroPracticeProgress::class)->find((string) $user->id))->toBeNull();
    }
});

it('requires web authentication for production review', function (): void {
    app()->instance('env', 'production');
    config(['descubro.production_review_enabled' => true]);
    expect(Crossing::enabled())->toBeFalse();
    $this->get('/descubro/cruzar-acompanado')->assertRedirect(route('login'));
});
