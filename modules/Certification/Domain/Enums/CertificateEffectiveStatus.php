<?php

declare(strict_types=1);

namespace Modules\Certification\Domain\Enums;

enum CertificateEffectiveStatus: string
{
    case Valid = 'valid';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
