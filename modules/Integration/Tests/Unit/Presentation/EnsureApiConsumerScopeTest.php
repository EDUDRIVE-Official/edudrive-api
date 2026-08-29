<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Modules\Integration\Presentation\Http\Middleware\EnsureApiConsumerScope;

it('permite el paso cuando el consumidor tiene el alcance requerido', function (): void {
    $middleware = new EnsureApiConsumerScope;

    $request = Request::create('/api/v1/external/reports/ping', 'GET');
    $request->attributes->set('authenticated_api_consumer_scopes', ['reports.view']);

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]), 'reports.view');

    expect($response->getStatusCode())->toBe(200);
});

it('rechaza cuando el consumidor no tiene el alcance requerido', function (): void {
    $middleware = new EnsureApiConsumerScope;

    $request = Request::create('/api/v1/external/reports/ping', 'GET');
    $request->attributes->set('authenticated_api_consumer_scopes', ['users.view']);

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]), 'reports.view');

    expect($response->getStatusCode())->toBe(403);
});
