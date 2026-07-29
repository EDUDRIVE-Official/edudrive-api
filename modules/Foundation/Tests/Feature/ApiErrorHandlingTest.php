<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Foundation\Presentation\Http\Middleware\CorrelationId;
use Tests\TestCase;

beforeEach(function (): void {
    Route::post('/api/v1/test-validation', function (): array {
        request()->validate([
            'name' => ['required', 'string'],
        ]);

        return [
            'success' => true,
        ];
    });
});

it('devuelve la estructura oficial para rutas inexistentes', function (): void {
    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/recurso-inexistente');

    $response
        ->assertNotFound()
        ->assertHeader(CorrelationId::HEADER)
        ->assertExactJson([
            'success' => false,
            'message' => 'El recurso solicitado no existe.',
            'code' => 'RESOURCE_NOT_FOUND',
        ]);
});

it('devuelve errores de validación con estado 422', function (): void {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/test-validation', []);

    $response
        ->assertUnprocessable()
        ->assertHeader(CorrelationId::HEADER)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Los datos enviados no son válidos.')
        ->assertJsonPath('code', 'VALIDATION_ERROR');

});
