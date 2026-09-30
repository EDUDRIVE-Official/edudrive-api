<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Services;

interface CertificateIssuer
{
    public function issue(string $userId, string $courseId): void;
}
