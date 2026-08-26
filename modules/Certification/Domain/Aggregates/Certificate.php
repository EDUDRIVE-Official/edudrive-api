<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Certification\Domain\Enums\CertificateEffectiveStatus;
use Modules\Certification\Domain\Enums\CertificateStatus;
use Modules\Certification\Domain\Exceptions\InvalidCertificateTransition;
use Modules\Certification\Domain\ValueObjects\CertificateHistoryEntry;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;

final class Certificate
{
    /** @param list<CertificateHistoryEntry> $history */
    private function __construct(
        private CertificateId $id,
        private string $userId,
        private string $courseId,
        private ValidationCode $validationCode,
        private CertificateStatus $status,
        private DateTimeImmutable $issuedAt,
        private ?DateTimeImmutable $expiresAt,
        private array $history,
    ) {}

    public static function create(
        CertificateId $id,
        string $userId,
        string $courseId,
        ValidationCode $validationCode,
        ?DateTimeImmutable $expiresAt = null,
        ?DateTimeImmutable $issuedAt = null,
    ): self {
        return new self(
            $id,
            $userId,
            $courseId,
            $validationCode,
            CertificateStatus::Issued,
            $issuedAt ?? new DateTimeImmutable('now'),
            $expiresAt,
            [],
        );
    }

    /** @param list<CertificateHistoryEntry> $history */
    public static function restore(
        CertificateId $id,
        string $userId,
        string $courseId,
        ValidationCode $validationCode,
        CertificateStatus $status,
        DateTimeImmutable $issuedAt,
        ?DateTimeImmutable $expiresAt,
        array $history,
    ): self {
        return new self($id, $userId, $courseId, $validationCode, $status, $issuedAt, $expiresAt, $history);
    }

    public function revoke(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === CertificateStatus::Revoked) {
            throw InvalidCertificateTransition::create();
        }

        $this->history[] = CertificateHistoryEntry::statusChanged($this->status, CertificateStatus::Revoked, $at, $reason);
        $this->status = CertificateStatus::Revoked;
    }

    public function id(): CertificateId
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function courseId(): string
    {
        return $this->courseId;
    }

    public function validationCode(): ValidationCode
    {
        return $this->validationCode;
    }

    public function status(): CertificateStatus
    {
        return $this->status;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /** @return list<CertificateHistoryEntry> */
    public function history(): array
    {
        return $this->history;
    }

    public function effectiveStatus(DateTimeImmutable $now): CertificateEffectiveStatus
    {
        if ($this->status === CertificateStatus::Revoked) {
            return CertificateEffectiveStatus::Revoked;
        }

        if ($this->expiresAt !== null && $this->expiresAt < $now) {
            return CertificateEffectiveStatus::Expired;
        }

        return CertificateEffectiveStatus::Valid;
    }
}
