<?php

declare(strict_types=1);

namespace Modules\Certification\Application\UseCases;

use Modules\Certification\Application\Queries\GetMyCertificatesQuery;
use Modules\Certification\Application\Responses\CertificateResponse;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;

final readonly class GetMyCertificatesHandler
{
    public function __construct(private CertificateRepository $certificates) {}

    /** @return list<CertificateResponse> */
    public function handle(GetMyCertificatesQuery $query): array
    {
        return array_map(
            static fn (Certificate $certificate): CertificateResponse => CertificateResponse::fromCertificate($certificate),
            $this->certificates->allForUser($query->userId),
        );
    }
}
