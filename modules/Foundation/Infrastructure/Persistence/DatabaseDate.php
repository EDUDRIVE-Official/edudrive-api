<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Persistence;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final class DatabaseDate
{
    public static function queryValue(DateTimeInterface $value): string
    {
        $date = self::normalize($value);

        return $date->format(DB::getDriverName() === 'pgsql' ? 'Y-m-d H:i:sP' : 'Y-m-d H:i:s');
    }

    public static function restore(mixed $value): DateTimeImmutable
    {
        return $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string) $value);
    }

    /** @return ($value is null ? null : DateTimeImmutable) */
    public static function normalize(?DateTimeInterface $value): ?DateTimeImmutable
    {
        // SQL timestamps omit the offset. Match the zone used by Eloquent on read.
        return $value === null ? null : DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone((string) config('app.timezone')));
    }
}
