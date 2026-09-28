<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Enums\Role;

it('restricts editorial preview to course managers', function (): void {
    $this->get('/pilot-instruments/editorial-preview')->assertRedirect(route('login'));
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/editorial-preview')->assertForbidden();
});

it('renders all five draft lessons without a completion form', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    foreach (range(1, 5) as $number) {
        $this->get('/pilot-instruments/editorial-preview?lesson='.$number)
            ->assertOk()->assertSee('Lección '.$number.' de 5')
            ->assertSee('Las respuestas no se guardan')->assertDontSee('Completar lección');
    }
});

it('rejects an out of range lesson', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->getJson('/pilot-instruments/editorial-preview?lesson=6')->assertUnprocessable();
});

it('presents every lesson one page at a time', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    foreach (range(1, 5) as $number) {
    foreach (range(1, 5) as $page) {
        $response = $this->get('/pilot-instruments/editorial-preview?lesson='.$number.'&page='.$page)
            ->assertOk()->assertSee('Página '.$page.' de 5')
            ->assertSee('Lección '.$number.' de 5')
            ->assertSee('Guía para el acompañante')->assertDontSee('Completar lección');
        if ($page === 2) {
            $response->assertSee('✓ Respuesta correcta')->assertSee('⚠ Respuesta incorrecta')
                ->assertSee('✓ ¡Acertaste! Respuesta correcta')->assertSee('⚠ Esta respuesta no es correcta. Revisemos el riesgo.')
                ->assertSee('aria-atomic="true"', false)->assertSee('passenger-response');
            $response->assertSee('editorial-'.$number.'-2')->assertDontSee('editorial-'.$number.'-3');
        } elseif ($page === 3) {
            $response->assertSee('✓ Respuesta correcta')->assertSee('⚠ Respuesta incorrecta')
                ->assertSee('✓ ¡Acertaste! Respuesta correcta')->assertSee('⚠ Esta respuesta no es correcta. Revisemos el riesgo.')
                ->assertSee('aria-atomic="true"', false)->assertSee('passenger-response');
            $response->assertSee('editorial-'.$number.'-3')->assertDontSee('editorial-'.$number.'-2');
        } else {
            $response->assertDontSee('editorial-'.$number.'-2')->assertDontSee('editorial-'.$number.'-3');
        }
        if ($page < 5) {
            $response->assertSee(route('pilot-instruments.editorial-preview', ['lesson' => $number, 'page' => $page + 1]).'#lesson-page');
        }
        if ($page > 1) {
            $response->assertSee(route('pilot-instruments.editorial-preview', ['lesson' => $number, 'page' => $page - 1]).'#lesson-page');
        }
        if ($number >= 4 && in_array($page, [2, 3], true)) {
            $response->assertSee('passengerPreview3d')->assertSee('Abrí la práctica 3D')->assertDontSee('representación básica');
        }
        if ($page === 5 && $number < 5) {
            $response->assertSee(route('pilot-instruments.editorial-preview', ['lesson' => $number + 1, 'page' => 1]).'#lesson-page');
        }
    }
    }
});

it('renders the specific supporting content for each lesson', function (): void {
    $supports = require resource_path('curriculum/editorial/page-support.php');
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    foreach ($supports as $number => $support) {
        $this->get('/pilot-instruments/editorial-preview?lesson='.$number)
            ->assertOk()->assertSee($support['mission'])->assertSee($support['more']);
        foreach ([2, 3] as $page) {
            $this->get('/pilot-instruments/editorial-preview?lesson='.$number.'&page='.$page)
                ->assertOk()->assertSee($support['reflection'][$page - 2]);
        }
        $this->get('/pilot-instruments/editorial-preview?lesson='.$number.'&page=4')
            ->assertOk()->assertSee($support['activity'][0]);
        $this->get('/pilot-instruments/editorial-preview?lesson='.$number.'&page=5')
            ->assertOk()->assertSee($support['takeaway'])->assertSee($support['remember'][0]);
    }
});

it('rejects invalid preview page numbers', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    foreach (['0', '6', 'abc', '1.5'] as $page) {
        $this->getJson('/pilot-instruments/editorial-preview?page='.$page)->assertUnprocessable();
    }
});
