<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use DateTimeImmutable;
use Modules\Certification\Application\Commands\RevokeCertificateCommand;
use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;

final readonly class RevokeCertificateHandler
{
    public function __construct(private CertificateRepository $certificates) {}

    public function handle(RevokeCertificateCommand $command): CertificateResponse
    {
        $certificate = $this->certificates->findById(CertificateId::fromString($command->certificateId));
        if ($certificate === null) {
            throw CertificateNotFound::withId($command->certificateId);
        }

        $certificate->revoke($command->reason, new DateTimeImmutable('now'));
        $this->certificates->save($certificate);

        return CertificateResponse::fromCertificate($certificate);
    }
}
