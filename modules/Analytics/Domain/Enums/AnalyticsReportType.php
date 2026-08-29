<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Enums;

enum AnalyticsReportType: string
{
    case EnrollmentsSummary = 'enrollments_summary';
    case CertificationsSummary = 'certifications_summary';
    case UsersSummary = 'users_summary';
}
