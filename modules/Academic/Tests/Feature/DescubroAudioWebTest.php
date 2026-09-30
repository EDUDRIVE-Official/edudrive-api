<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Academic\Infrastructure\Services\DescubroPracticeProgress;
use Modules\Authorization\Domain\Enums\Role;

it('offers optional audio without advancing the practice or exposing feedback before answering', function (): void {
    $user = actingAsRole(Role::Student);
    $this->actingAs($user, 'web');
    crossingBegin();
    $before = app(DescubroPracticeProgress::class)->find((string) $user->id);
    $this->get('/descubro/cruzar-acompanado')->assertOk()
        ->assertSee('js/descubro-read-aloud.js')->assertSeeText('Escuchar esta pantalla')
        ->assertSeeText('Detener')->assertSee('data-dc-read', false)
        ->assertDontSeeText('La acera es el espacio para caminar. Nos quedamos juntos.');
    expect(app(DescubroPracticeProgress::class)->find((string) $user->id))->toBe($before);
    crossingAction('answer', 1)->assertRedirect();
    $this->get('/descubro/cruzar-acompanado')->assertOk()
        ->assertSeeText('La acera es el espacio para caminar. Nos quedamos juntos.')
        ->assertDontSeeText('Por la calzada');
    expect(DB::table('road_passport_evidence')->count())->toBe(0);
});
