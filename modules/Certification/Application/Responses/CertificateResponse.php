<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Responses;

use DateTimeInterface;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\ValueObjects\CertificateHistoryEntry;

final readonly class CertificateResponse
{
    /**
     * @param  list<array{from: string, to: string, occurred_at: string, reason: ?string}>  $history
     */
    public function __construct(
        public string $id,
        public ?string $userId,
        public string $courseId,
        public string $validationCode,
        public string $status,
        public string $issuedAt,
        public ?string $expiresAt,
        public array $history,
    ) {}

    public static function fromCertificate(Certificate $certificate): self
    {
        return new self(
            id: $certificate->id()->value(),
            userId: $certificate->userId(),
            courseId: $certificate->courseId(),
            validationCode: $certificate->validationCode()->value(),
            status: $certificate->status()->value,
            issuedAt: $certificate->issuedAt()->format(DateTimeInterface::ATOM),
            expiresAt: $certificate->expiresAt()?->format(DateTimeInterface::ATOM),
            history: array_map(
                static fn (CertificateHistoryEntry $entry): array => [
                    'from' => $entry->fromStatus->value,
                    'to' => $entry->toStatus->value,
                    'occurred_at' => $entry->occurredAt->format(DateTimeInterface::ATOM),
                    'reason' => $entry->reason,
                ],
                $certificate->history(),
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     user_id: ?string,
     *     course_id: string,
     *     validation_code: string,
     *     status: string,
     *     issued_at: string,
     *     expires_at: ?string,
     *     history: list<array{from: string, to: string, occurred_at: string, reason: ?string}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'validation_code' => $this->validationCode,
            'status' => $this->status,
            'issued_at' => $this->issuedAt,
            'expires_at' => $this->expiresAt,
            'history' => $this->history,
        ];
    }
}
