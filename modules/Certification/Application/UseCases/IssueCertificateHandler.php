<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Exceptions\CertificateAlreadyExists;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;

final readonly class IssueCertificateHandler
{
    public function __construct(private CertificateRepository $certificates) {}

    public function handle(IssueCertificateCommand $command): CertificateResponse
    {
        if ($this->certificates->findByUserAndCourse($command->userId, $command->courseId) !== null) {
            throw CertificateAlreadyExists::create();
        }

        $certificate = Certificate::create(
            id: CertificateId::fromString((string) Str::uuid()),
            userId: $command->userId,
            courseId: $command->courseId,
            validationCode: ValidationCode::generate(),
            expiresAt: $command->expiresAt,
        );

        $this->certificates->save($certificate);

        return CertificateResponse::fromCertificate($certificate);
    }
}
