<?php

declare(strict_types=1);

namespace Modules\Certification\Infrastructure\Services;

use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Services\CertificateIssuer;
use Modules\Certification\Application\UseCases\IssueCertificateHandler;

final readonly class DefaultCertificateIssuer implements CertificateIssuer
{
    public function __construct(private IssueCertificateHandler $handler) {}

    public function issue(string $userId, string $courseId): void
    {
        $this->handler->handle(new IssueCertificateCommand($userId, $courseId));
    }
}
