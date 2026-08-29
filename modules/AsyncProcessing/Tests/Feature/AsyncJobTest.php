<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('requiere autenticacion para consultar un trabajo asincrono', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/async-jobs/'.Str::uuid())->assertUnauthorized();
});

it('permite al solicitante consultar el estado de su propio trabajo asincrono', function (): void {
    /** @var TestCase $this */
    $user = actingAsRole(Role::Student);
    $job = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.enrollments', (string) $user->id);
    app(AsyncJobRepository::class)->save($job);

    $this->getJson("/api/v1/async-jobs/{$job->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.type', 'export.enrollments');
});

it('rechaza consultar el trabajo asincrono de otro usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $job = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.enrollments', (string) Str::uuid());
    app(AsyncJobRepository::class)->save($job);

    $this->getJson("/api/v1/async-jobs/{$job->id()->value()}")
        ->assertStatus(404);
});

it('rechaza un identificador inexistente', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/async-jobs/'.Str::uuid())
        ->assertStatus(404)
        ->assertJsonPath('code', 'ASYNC_JOB_NOT_FOUND');
});
