<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Enums\CertificateStatus;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateHistoryEntryModel;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Models\CertificateModel;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedCertificateUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Titular de certificado',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera un certificado por identificador', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $userId = persistedCertificateUserId();
    $validationCode = ValidationCode::generate();
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $userId,
        courseId: $course->id()->value(),
        validationCode: $validationCode,
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );

    app(CertificateRepository::class)->save($certificate);
    $found = app(CertificateRepository::class)->findById($certificate->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($certificate->id()))->toBeTrue()
        ->and($found?->userId())->toBe($userId)
        ->and($found?->courseId())->toBe($course->id()->value())
        ->and($found?->validationCode()->equals($validationCode))->toBeTrue()
        ->and($found?->status())->toBe(CertificateStatus::Issued)
        ->and($found?->expiresAt())->toBeNull()
        ->and($found?->history())->toBe([]);
});

it('guarda y recupera la vigencia y el historial', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $repository = app(CertificateRepository::class);
    $expiresAt = new DateTimeImmutable('2027-08-26T10:00:00+00:00');
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: persistedCertificateUserId(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
        expiresAt: $expiresAt,
        issuedAt: new DateTimeImmutable('now'),
    );
    $certificate->revoke('Motivo', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));
    $repository->save($certificate);

    $found = $repository->findById($certificate->id());

    expect($found?->expiresAt())->toEqual($expiresAt)
        ->and($found?->status())->toBe(CertificateStatus::Revoked)
        ->and($found?->history())->toHaveCount(1)
        ->and($found?->history()[0]->reason)->toBe('Motivo');
});

it('encuentra un certificado por codigo de validacion', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $userId = persistedCertificateUserId();
    $validationCode = ValidationCode::generate();
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $userId,
        courseId: $course->id()->value(),
        validationCode: $validationCode,
    );
    app(CertificateRepository::class)->save($certificate);

    $found = app(CertificateRepository::class)->findByValidationCode($validationCode);

    expect($found?->id()->equals($certificate->id()))->toBeTrue();
    expect(app(CertificateRepository::class)->findByValidationCode(ValidationCode::generate()))->toBeNull();
});

it('encuentra un certificado por usuario y curso', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $userId = persistedCertificateUserId();
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $userId,
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    app(CertificateRepository::class)->save($certificate);

    $found = app(CertificateRepository::class)->findByUserAndCourse($userId, $course->id()->value());

    expect($found?->id()->equals($certificate->id()))->toBeTrue();
    expect(app(CertificateRepository::class)->findByUserAndCourse($userId, (string) Str::uuid()))->toBeNull();
});

it('lista todos los certificados de un usuario', function (): void {
    $courseA = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $courseB = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $userId = persistedCertificateUserId();
    $repository = app(CertificateRepository::class);

    $repository->save(Certificate::create(CertificateId::fromString((string) Str::uuid()), $userId, $courseA->id()->value(), ValidationCode::generate()));
    $repository->save(Certificate::create(CertificateId::fromString((string) Str::uuid()), $userId, $courseB->id()->value(), ValidationCode::generate()));
    $repository->save(Certificate::create(CertificateId::fromString((string) Str::uuid()), persistedCertificateUserId(), $courseA->id()->value(), ValidationCode::generate()));

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('reemplaza el historial en vez de duplicarlo al guardar de nuevo', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $repository = app(CertificateRepository::class);
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: persistedCertificateUserId(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    $certificate->revoke(null, new DateTimeImmutable('now'));
    $repository->save($certificate);
    $repository->save($certificate);

    $found = $repository->findById($certificate->id());

    expect($found?->history())->toHaveCount(1);
});

it('borra en cascada el historial al eliminar el certificado', function (): void {
    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));
    $repository = app(CertificateRepository::class);
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: persistedCertificateUserId(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    $certificate->revoke(null, new DateTimeImmutable('now'));
    $repository->save($certificate);

    CertificateModel::query()->where('id', $certificate->id()->value())->delete();

    expect(CertificateHistoryEntryModel::query()->where('certificate_id', $certificate->id()->value())->count())->toBe(0);
});
