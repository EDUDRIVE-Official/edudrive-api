<?php

declare(strict_types=1);

use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Academic\Presentation\Http\Controllers\DescubroCrossingController as Crossing;
use Modules\Authorization\Domain\Enums\Role;

it('enables descubro staging only through an explicit flag and never enables production', function (): void {
    foreach (['local', 'testing', 'staging', 'production', 'qa'] as $environment) {
        app()->instance('env', $environment);
        foreach ([false, true] as $flag) {
            config(['descubro.staging_enabled' => $flag]);
            expect(Crossing::enabled())->toBe(in_array($environment, ['local', 'testing'], true) || ($environment === 'staging' && $flag));
        }
    }
});

it('keeps staging routes closed by default and authenticates when explicitly enabled', function (): void {
    app()->instance('env', 'staging');
    config(['descubro.staging_enabled' => true]);
    $this->get('/descubro/cruzar-acompanado?page=review')->assertRedirect(route('login'));
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    config(['descubro.staging_enabled' => false]);
    $this->get('/descubro/cruzar-acompanado')->assertNotFound();
    $this->withSession(['_token' => 'staging-test'])->post('/descubro/cruzar-acompanado', [
        '_token' => 'staging-test', 'action' => 'start', 'revision' => 0,
    ])->assertNotFound();
    config(['descubro.staging_enabled' => true]);
    $this->get('/descubro/cruzar-acompanado?page=review')->assertOk()->assertSeeText('Camino por la acera');
    $this->withSession(['_token' => 'staging-test'])->post('/descubro/cruzar-acompanado', [
        '_token' => 'staging-test', 'action' => 'start', 'revision' => 0,
    ])->assertRedirect('/descubro/cruzar-acompanado');
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id)['phase'])->toBe('learn');
    app()->instance('env', 'production');
    $this->get('/descubro/cruzar-acompanado?page=review')->assertNotFound();
    $this->withSession(['_token' => 'staging-test'])->post('/descubro/cruzar-acompanado', [
        '_token' => 'staging-test', 'action' => 'lesson-next', 'revision' => 1,
    ])->assertNotFound();
});
