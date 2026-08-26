<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\ValueObjects;

use DateTimeImmutable;
use Modules\Certification\Domain\Enums\CertificateStatus;

final readonly class CertificateHistoryEntry
{
    private function __construct(
        public CertificateStatus $fromStatus,
        public CertificateStatus $toStatus,
        public DateTimeImmutable $occurredAt,
        public ?string $reason,
    ) {}

    public static function statusChanged(
        CertificateStatus $from,
        CertificateStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }

    public static function restore(
        CertificateStatus $from,
        CertificateStatus $to,
        DateTimeImmutable $occurredAt,
        ?string $reason,
    ): self {
        return new self($from, $to, $occurredAt, $reason);
    }
}
