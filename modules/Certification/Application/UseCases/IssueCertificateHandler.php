<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Webhook\Application\Services\WebhookEventPublisher;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\ValueObjects\WebhookEvent;

final readonly class IssueCertificateHandler
{
    public function __construct(
        private CertificateRepository $certificates,
        private WebhookEventPublisher $webhookEventPublisher,
    ) {}

    public function handle(IssueCertificateCommand $command): CertificateResponse
    {
        $existing = $this->certificates->findByUserAndCourse($command->userId, $command->courseId);
        if ($existing !== null) {
            return CertificateResponse::fromCertificate($existing);
        }

        $certificate = Certificate::create(
            id: CertificateId::fromString((string) Str::uuid()),
            userId: $command->userId,
            courseId: $command->courseId,
            validationCode: ValidationCode::generate(),
            expiresAt: $command->expiresAt,
        );

        $this->certificates->save($certificate);

        $this->webhookEventPublisher->publish(new WebhookEvent(
            name: WebhookEventName::CertificateIssued,
            payload: [
                'certificate_id' => $certificate->id()->value(),
                'user_id' => $certificate->userId(),
                'course_id' => $certificate->courseId(),
            ],
            occurredAt: $certificate->issuedAt(),
        ));

        return CertificateResponse::fromCertificate($certificate);
    }
}
