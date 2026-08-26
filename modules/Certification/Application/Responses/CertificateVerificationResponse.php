<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Certification\Domain\Aggregates\Certificate;

final readonly class CertificateVerificationResponse
{
    public function __construct(
        public string $validationCode,
        public string $status,
        public string $issuedAt,
        public ?string $expiresAt,
        public string $courseId,
        public string $courseName,
        public string $holderName,
    ) {}

    public static function fromCertificate(
        Certificate $certificate,
        string $courseName,
        string $holderName,
        ?DateTimeImmutable $now = null,
    ): self {
        $now ??= new DateTimeImmutable('now');

        return new self(
            validationCode: $certificate->validationCode()->value(),
            status: $certificate->effectiveStatus($now)->value,
            issuedAt: $certificate->issuedAt()->format(DateTimeInterface::ATOM),
            expiresAt: $certificate->expiresAt()?->format(DateTimeInterface::ATOM),
            courseId: $certificate->courseId(),
            courseName: $courseName,
            holderName: $holderName,
        );
    }

    /**
     * @return array{
     *     validation_code: string,
     *     status: string,
     *     issued_at: string,
     *     expires_at: ?string,
     *     course_id: string,
     *     course_name: string,
     *     holder_name: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'validation_code' => $this->validationCode,
            'status' => $this->status,
            'issued_at' => $this->issuedAt,
            'expires_at' => $this->expiresAt,
            'course_id' => $this->courseId,
            'course_name' => $this->courseName,
            'holder_name' => $this->holderName,
        ];
    }
}
