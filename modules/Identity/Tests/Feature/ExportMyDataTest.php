<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Tests\TestCase;

function registerAccountForExport(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario exportador',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

function loginAccountForExport(TestCase $test, User $user): string
{
    $login = $test->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->assertOk();

    return $login->json('data.token.access_token');
}

it('requiere autenticacion para exportar los datos personales', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/auth/me/data-export')->assertUnauthorized();
});

it('exporta el perfil y agrega listas vacias para modulos sin datos', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForExport();
    $token = loginAccountForExport($this, $user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/data-export')
        ->assertOk()
        ->assertJsonPath('data.profile.id', $user->id())
        ->assertJsonPath('data.profile.email', $user->email()->value())
        ->assertJsonPath('data.certificates', [])
        ->assertJsonPath('data.role_assignments', [])
        ->assertJsonPath('data.consents', [])
        ->assertJsonPath('data.road_passport', null)
        ->assertJsonPath('data.notification_preferences', null);
});

it('incluye asignaciones de rol, certificados y consentimientos del usuario', function (): void {
    /** @var TestCase $this */
    $user = registerAccountForExport();
    $token = loginAccountForExport($this, $user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    app(CertificateRepository::class)->save(Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    ));

    app(CommandBus::class)->dispatch(new PublishPolicyVersionCommand(key: 'privacy_policy'));
    app(CommandBus::class)->dispatch(new RecordConsentCommand(userId: $user->id(), policyKey: 'privacy_policy'));

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/data-export')
        ->assertOk();

    $response
        ->assertJsonPath('data.role_assignments.0.role', 'student')
        ->assertJsonPath('data.certificates.0.validation_code', fn (string $code): bool => $code !== '')
        ->assertJsonPath('data.consents.0.policy_key', 'privacy_policy')
        ->assertJsonPath('data.consents.0.policy_version', 1);
});
