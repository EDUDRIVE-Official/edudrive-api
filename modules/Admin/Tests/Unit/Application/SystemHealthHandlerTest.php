<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Responses\SystemHealthResponse;
use Modules\Admin\Application\UseCases\GetSystemHealthHandler;

uses(RefreshDatabase::class);

it('reporta la base de datos como activa cuando la conexion responde', function (): void {
    $response = (new GetSystemHealthHandler)->handle(new GetSystemHealthQuery);

    expect($response)->toBeInstanceOf(SystemHealthResponse::class)
        ->and($response->status)->toBe('healthy')
        ->and($response->database)->toBe('up');
});
