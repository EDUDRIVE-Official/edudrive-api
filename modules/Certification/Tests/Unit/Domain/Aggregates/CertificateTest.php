<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Enums\CertificateStatus;
use Modules\Certification\Domain\Exceptions\InvalidCertificateTransition;
use Modules\Certification\Domain\ValueObjects\CertificateHistoryEntry;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;

function newCertificate(?DateTimeImmutable $expiresAt = null): Certificate
{
    return Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: (string) Str::uuid(),
        courseId: (string) Str::uuid(),
        validationCode: ValidationCode::generate(),
        expiresAt: $expiresAt,
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se emite en estado issued y sin historial', function (): void {
    $certificate = newCertificate();

    expect($certificate->status())->toBe(CertificateStatus::Issued)
        ->and($certificate->history())->toBe([])
        ->and($certificate->expiresAt())->toBeNull();
});

it('acepta una fecha de vigencia opcional', function (): void {
    $expiresAt = new DateTimeImmutable('2027-08-26T10:00:00+00:00');
    $certificate = newCertificate($expiresAt);

    expect($certificate->expiresAt())->toBe($expiresAt);
});

it('revoca un certificado emitido y registra el cambio en el historial', function (): void {
    $certificate = newCertificate();

    $certificate->revoke('Fraude detectado', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($certificate->status())->toBe(CertificateStatus::Revoked)
        ->and($certificate->history())->toHaveCount(1);

    $entry = $certificate->history()[0];
    expect($entry->fromStatus)->toBe(CertificateStatus::Issued)
        ->and($entry->toStatus)->toBe(CertificateStatus::Revoked)
        ->and($entry->reason)->toBe('Fraude detectado');
});

it('rechaza revocar un certificado ya revocado', function (): void {
    $certificate = newCertificate();
    $certificate->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $certificate->revoke(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidCertificateTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = CertificateId::fromString((string) Str::uuid());
    $userId = (string) Str::uuid();
    $courseId = (string) Str::uuid();
    $validationCode = ValidationCode::generate();
    $issuedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $expiresAt = new DateTimeImmutable('2027-08-26T10:00:00+00:00');
    $historyEntry = CertificateHistoryEntry::restore(
        CertificateStatus::Issued,
        CertificateStatus::Revoked,
        new DateTimeImmutable('2026-08-27T00:00:00+00:00'),
        'Motivo',
    );

    $certificate = Certificate::restore(
        id: $id,
        userId: $userId,
        courseId: $courseId,
        validationCode: $validationCode,
        status: CertificateStatus::Revoked,
        issuedAt: $issuedAt,
        expiresAt: $expiresAt,
        history: [$historyEntry],
    );

    expect($certificate->id()->equals($id))->toBeTrue()
        ->and($certificate->userId())->toBe($userId)
        ->and($certificate->courseId())->toBe($courseId)
        ->and($certificate->validationCode()->equals($validationCode))->toBeTrue()
        ->and($certificate->status())->toBe(CertificateStatus::Revoked)
        ->and($certificate->issuedAt())->toBe($issuedAt)
        ->and($certificate->expiresAt())->toBe($expiresAt)
        ->and($certificate->history())->toBe([$historyEntry]);
});
