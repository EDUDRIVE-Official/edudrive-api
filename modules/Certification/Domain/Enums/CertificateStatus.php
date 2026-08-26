<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Enums;

enum CertificateStatus: string
{
    case Issued = 'issued';
    case Revoked = 'revoked';
}
