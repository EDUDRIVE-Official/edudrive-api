<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Tests\TestCase;

/** @return array{0: ApiConsumer, 1: string} */
function persistedRateLimitingApiConsumer(): array
{
    $integrationKey = IntegrationKey::generate();
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: ['reports.view'],
        integrationKey: $integrationKey,
    );
    app(ApiConsumerRepository::class)->save($consumer);

    return [$consumer, (string) $integrationKey->plainValue()];
}

it('limita el acceso externo a 60 por minuto por consumidor', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedRateLimitingApiConsumer();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->withToken($token)->getJson('/api/v1/external/status');
    }

    $this->withToken($token)->getJson('/api/v1/external/status')
        ->assertStatus(429)
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});

it('no comparte el limite de integracion entre consumidores distintos', function (): void {
    /** @var TestCase $this */
    [, $tokenA] = persistedRateLimitingApiConsumer();
    [, $tokenB] = persistedRateLimitingApiConsumer();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->withToken($tokenA)->getJson('/api/v1/external/status');
    }

    $this->withToken($tokenA)->getJson('/api/v1/external/status')->assertStatus(429);

    $response = $this->withToken($tokenB)->getJson('/api/v1/external/status');
    expect($response->status())->not->toBe(429);
});
