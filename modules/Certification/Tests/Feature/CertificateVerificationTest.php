<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedVerifiableCertificate(?DateTimeImmutable $expiresAt = null): array
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Ana Torres',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));

    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
        expiresAt: $expiresAt,
    );
    app(CertificateRepository::class)->save($certificate);

    return ['certificate' => $certificate, 'user' => $user, 'course' => $course];
}

it('verifica un certificado valido sin autenticacion', function (): void {
    ['certificate' => $certificate, 'user' => $user, 'course' => $course] = persistedVerifiableCertificate();
    /** @var Course $course */
    $this->getJson("/api/v1/certification/verify/{$certificate->validationCode()->value()}")
        ->assertOk()
        ->assertJsonPath('data.validation_code', $certificate->validationCode()->value())
        ->assertJsonPath('data.status', 'valid')
        ->assertJsonPath('data.holder_name', $user->name())
        ->assertJsonPath('data.course_id', $course->id()->value())
        ->assertJsonPath('data.course_name', $course->title()->value())
        ->assertJsonMissingPath('data.user_id')
        ->assertJsonMissingPath('data.id');
});

it('verifica un codigo recibido en minusculas', function (): void {
    ['certificate' => $certificate] = persistedVerifiableCertificate();
    $lowercase = strtolower($certificate->validationCode()->value());

    $this->getJson("/api/v1/certification/verify/{$lowercase}")
        ->assertOk()
        ->assertJsonPath('data.validation_code', $certificate->validationCode()->value());
});

it('reporta expired cuando la vigencia ya paso', function (): void {
    ['certificate' => $certificate] = persistedVerifiableCertificate(new DateTimeImmutable('2020-01-01T00:00:00+00:00'));

    $this->getJson("/api/v1/certification/verify/{$certificate->validationCode()->value()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'expired');
});

it('reporta revoked para un certificado revocado', function (): void {
    ['certificate' => $certificate] = persistedVerifiableCertificate();
    $certificate->revoke('Fraude', new DateTimeImmutable('now'));
    app(CertificateRepository::class)->save($certificate);

    $this->getJson("/api/v1/certification/verify/{$certificate->validationCode()->value()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('responde 404 con un codigo inexistente', function (): void {
    $this->getJson('/api/v1/certification/verify/'.ValidationCode::generate()->value())
        ->assertNotFound()
        ->assertJsonPath('code', 'CERTIFICATE_NOT_FOUND');
});

it('responde 404 con un codigo de formato invalido', function (): void {
    $this->getJson('/api/v1/certification/verify/no-es-un-codigo-valido')
        ->assertNotFound();
});
