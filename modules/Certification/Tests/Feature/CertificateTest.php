<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedCertificateFeatureUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Titular de certificado feature',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

function persistedCertificateFeature(?string $userId = null, ?string $courseId = null): Certificate
{
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $userId ?? persistedCertificateFeatureUserId(),
        courseId: $courseId ?? createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)))->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    app(CertificateRepository::class)->save($certificate);

    return $certificate;
}

it('emite un certificado con el permiso certifications.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedCertificateFeatureUserId();
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));

    $this->postJson('/api/v1/certification/certificates', [
        'user_id' => $userId,
        'course_id' => $course->id()->value(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $userId)
        ->assertJsonPath('data.course_id', $course->id()->value())
        ->assertJsonPath('data.status', 'issued')
        ->assertJsonPath('data.expires_at', null);
});

it('acepta una vigencia opcional al emitir un certificado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $userId = persistedCertificateFeatureUserId();
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));

    $this->postJson('/api/v1/certification/certificates', [
        'user_id' => $userId,
        'course_id' => $course->id()->value(),
        'expires_at' => '2027-08-26T00:00:00+00:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.expires_at', '2027-08-26T00:00:00+00:00');
});

it('rechaza emitir un certificado sin el permiso certifications.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));

    $this->postJson('/api/v1/certification/certificates', [
        'user_id' => (string) Str::uuid(),
        'course_id' => $course->id()->value(),
    ])->assertForbidden();
});

it('devuelve el certificado existente en vez de fallar ante un reintento (idempotencia)', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $certificate = persistedCertificateFeature();

    $this->postJson('/api/v1/certification/certificates', [
        'user_id' => $certificate->userId(),
        'course_id' => $certificate->courseId(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.id', $certificate->id()->value());
});

it('lista los certificados del usuario autenticado', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $certificate = persistedCertificateFeature($userId);

    $this->getJson('/api/v1/certification/certificates/me')
        ->assertOk()
        ->assertJsonPath('data.0.id', $certificate->id()->value());
});

it('consulta un certificado propio por id', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $certificate = persistedCertificateFeature($userId);

    $this->getJson("/api/v1/certification/certificates/{$certificate->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $certificate->id()->value());
});

it('rechaza consultar un certificado ajeno sin certifications.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);
    $certificate = persistedCertificateFeature();

    $this->getJson("/api/v1/certification/certificates/{$certificate->id()->value()}")
        ->assertNotFound()
        ->assertJsonPath('code', 'CERTIFICATE_NOT_FOUND');
});

it('permite consultar un certificado ajeno con certifications.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);
    $certificate = persistedCertificateFeature();

    $this->getJson("/api/v1/certification/certificates/{$certificate->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $certificate->id()->value());
});

it('revoca un certificado con el permiso certifications.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $certificate = persistedCertificateFeature();

    $this->postJson("/api/v1/certification/certificates/{$certificate->id()->value()}/revoke", ['reason' => 'Fraude'])
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('rechaza revocar un certificado sin el permiso certifications.manage', function (): void {
    /** @var TestCase $this */
    $certificate = persistedCertificateFeature();
    actingAsRole(Role::Teacher);

    $this->postJson("/api/v1/certification/certificates/{$certificate->id()->value()}/revoke")
        ->assertForbidden();
});

it('responde 422 al revocar dos veces el mismo certificado', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $certificate = persistedCertificateFeature();

    $this->postJson("/api/v1/certification/certificates/{$certificate->id()->value()}/revoke")->assertOk();

    $this->postJson("/api/v1/certification/certificates/{$certificate->id()->value()}/revoke")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_CERTIFICATE_TRANSITION');
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $certificate = persistedCertificateFeature();

    $this->getJson('/api/v1/certification/certificates/me')->assertUnauthorized();
    $this->getJson("/api/v1/certification/certificates/{$certificate->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/certification/certificates', ['user_id' => (string) Str::uuid(), 'course_id' => (string) Str::uuid()])->assertUnauthorized();
});
