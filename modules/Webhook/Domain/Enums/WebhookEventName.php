<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Enums;

enum WebhookEventName: string
{
    case EnrollmentCreated = 'enrollment.created';
    case CertificateIssued = 'certificate.issued';
}
