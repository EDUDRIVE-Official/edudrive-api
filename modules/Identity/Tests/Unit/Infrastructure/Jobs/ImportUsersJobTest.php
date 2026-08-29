<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Jobs\ImportUsersJob;

uses(RefreshDatabase::class);

final class InMemoryAsyncJobRepositoryForUsersImportJob implements AsyncJobRepository
{
    /** @var array<string, AsyncJob> */
    public array $items = [];

    public function save(AsyncJob $job): void
    {
        $this->items[$job->id()->value()] = $job;
    }

    public function findById(AsyncJobId $id): ?AsyncJob
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }

    public function delete(AsyncJobId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

function persistedImportUsersJobActorId(): string
{
    $actor = User::register(
        id: (string) Str::uuid(),
        name: 'Actor de importacion',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($actor);

    return $actor->id();
}

it('importa usuarios en lote y completa el trabajo asignando student por defecto', function (): void {
    $actorId = persistedImportUsersJobActorId();
    $jobs = new InMemoryAsyncJobRepositoryForUsersImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.users', $actorId));
    $email = sprintf('%s@edudrive.cr', Str::uuid());

    (new ImportUsersJob($asyncJobId->value(), [
        ['name' => 'Ana Perez', 'email' => $email, 'password' => 'secret123', 'role' => 'teacher'],
    ], $actorId))->handle(
        $jobs,
        app(UserRepository::class),
        app(PasswordHasher::class),
        app(UuidGenerator::class),
        app(CommandBus::class),
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->status())->toBe(AsyncJobStatus::Completed)
        ->and($completed?->result()['total'])->toBe(1)
        ->and($completed?->result()['created'])->toBe(1)
        ->and($completed?->result()['failed'])->toBe(0);

    $createdUserId = $completed?->result()['results'][0]['user_id'];
    $roles = app(RoleAssignmentRepository::class)->findByUserId((string) $createdUserId);
    expect($roles)->toHaveCount(1)->and($roles[0]->role())->toBe(Role::Teacher);
});

it('reporta una fila fallida por correo ya existente sin detener el resto del lote', function (): void {
    $actorId = persistedImportUsersJobActorId();
    $existing = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario existente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($existing);

    $jobs = new InMemoryAsyncJobRepositoryForUsersImportJob;
    $asyncJobId = AsyncJobId::fromString((string) Str::uuid());
    $jobs->save(AsyncJob::request($asyncJobId, 'import.users', $actorId));

    (new ImportUsersJob($asyncJobId->value(), [
        ['name' => 'Duplicado', 'email' => $existing->email()->value(), 'password' => 'secret123', 'role' => ''],
        ['name' => 'Nuevo', 'email' => sprintf('%s@edudrive.cr', Str::uuid()), 'password' => 'secret123', 'role' => ''],
    ], $actorId))->handle(
        $jobs,
        app(UserRepository::class),
        app(PasswordHasher::class),
        app(UuidGenerator::class),
        app(CommandBus::class),
    );

    $completed = $jobs->findById($asyncJobId);
    expect($completed?->result()['total'])->toBe(2)
        ->and($completed?->result()['created'])->toBe(1)
        ->and($completed?->result()['failed'])->toBe(1)
        ->and($completed?->result()['results'][0]['error_code'])->toBe('EMAIL_ALREADY_EXISTS')
        ->and($completed?->result()['results'][1]['created'])->toBeTrue();
});

it('captura el correlation_id activo al momento de crear el job', function (): void {
    Context::add('correlation_id', 'mi-correlation-id');

    $job = new ImportUsersJob((string) Str::uuid(), [], (string) Str::uuid());

    expect($job->correlationId)->toBe('mi-correlation-id');
});
