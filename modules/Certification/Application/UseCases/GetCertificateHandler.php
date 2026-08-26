<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use Modules\Certification\Application\Exceptions\CertificateNotFound;
use Modules\Certification\Application\Queries\GetCertificateQuery;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;

final readonly class GetCertificateHandler
{
    public function __construct(private CertificateRepository $certificates) {}

    public function handle(GetCertificateQuery $query): CertificateResponse
    {
        $certificate = $this->certificates->findById(CertificateId::fromString($query->certificateId));
        if ($certificate === null) {
            throw CertificateNotFound::withId($query->certificateId);
        }

        if ($certificate->userId() !== $query->userId && ! $query->canViewOthers) {
            throw CertificateNotFound::withId($query->certificateId);
        }

        return CertificateResponse::fromCertificate($certificate);
    }
}
