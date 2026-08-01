<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

beforeEach(function (): void {
    Route::middleware(['web'])
        ->get('/test-permission-web', fn (): string => 'contenido protegido')
        ->middleware('permission:organizations.manage');
});

it('responde con una página web (no JSON) cuando no hay sesión en una ruta que no es de la API', function (): void {
    /** @var TestCase $this */
    $response = $this->get('/test-permission-web');

    $response->assertUnauthorized();
    expect($response->headers->get('content-type'))->not->toContain('application/json');
});

it('responde con una página web (no JSON) cuando falta el permiso en una ruta que no es de la API', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user);

    $response = $this->get('/test-permission-web');

    $response->assertForbidden();
    expect($response->headers->get('content-type'))->not->toContain('application/json');
});
