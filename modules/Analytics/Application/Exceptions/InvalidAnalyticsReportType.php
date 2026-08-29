<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAnalyticsReportType extends DomainException
{
    public static function withValue(string $value): self
    {
        return new self(
            message: "\"{$value}\" no es un tipo de reporte de analitica valido.",
            errorCode: 'INVALID_ANALYTICS_REPORT_TYPE',
            statusCode: 422,
        );
    }
}
