<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Repositories;

use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\ValueObjects\CertificateId;

interface CertificateRepository
{
    public function save(Certificate $certificate): void;

    public function findById(CertificateId $id): ?Certificate;

    public function findByUserAndCourse(string $userId, string $courseId): ?Certificate;

    /** @return list<Certificate> */
    public function allForUser(string $userId): array;
}
