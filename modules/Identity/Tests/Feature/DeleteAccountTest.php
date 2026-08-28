<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function registerAccountForDeletion(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario a eliminar',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

function loginAccountForDeletion(TestCase $test, User $user): string
{
    $login = $test->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    return $login->json('data.token.access_token');
}

it('elimina la propia cuenta y revoca el token usado', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForDeletion();
    $token = loginAccountForDeletion($this, $user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/auth/me')
        ->assertOk();

    expect(UserModel::query()->find($user->id()))->toBeNull();

    // El guard de Sanctum memoiza el usuario resuelto por peticion (RequestGuard::$user);
    // sin esto, la siguiente llamada reutilizaria el usuario ya resuelto en la peticion
    // anterior dentro del mismo metodo de prueba, en vez de re-resolverlo desde cero.
    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('registra la eliminacion en el historial de auditoria', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForDeletion();
    $token = loginAccountForDeletion($this, $user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/auth/me')
        ->assertOk();

    $entry = AuditLogModel::query()->where('action', 'identity.account_deleted')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->entity_id)->toBe($user->id())
        ->and($entry->metadata['actor_id'])->toBe($user->id())
        ->and($entry->user_id)->toBeNull();
});

it('elimina en cascada las asignaciones de rol del usuario', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForDeletion();
    $token = loginAccountForDeletion($this, $user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/auth/me')
        ->assertOk();

    expect(app(RoleAssignmentRepository::class)->findByUserId($user->id()))->toBe([]);
});

it('conserva los certificados desvinculados en vez de eliminarlos', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForDeletion();
    $token = loginAccountForDeletion($this, $user);

    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    app(CertificateRepository::class)->save($certificate);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/auth/me')
        ->assertOk();

    $found = app(CertificateRepository::class)->findById($certificate->id());

    expect($found)->not->toBeNull()
        ->and($found?->userId())->toBeNull();
});

it('requiere autenticacion para eliminar la cuenta', function (): void {
    /** @var TestCase $this */
    $this->deleteJson('/api/v1/auth/me')->assertUnauthorized();
});
