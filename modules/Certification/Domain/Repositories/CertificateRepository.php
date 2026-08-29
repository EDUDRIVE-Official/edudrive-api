<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Repositories;

use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;

interface CertificateRepository
{
    public function save(Certificate $certificate): void;

    public function findById(CertificateId $id): ?Certificate;

    public function findByUserAndCourse(string $userId, string $courseId): ?Certificate;

    public function findByValidationCode(ValidationCode $validationCode): ?Certificate;

    /** @return list<Certificate> */
    public function allForUser(string $userId): array;

    /** @return list<Certificate> */
    public function all(): array;
}
