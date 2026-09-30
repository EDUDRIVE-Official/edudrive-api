<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Persistence;

trait PreservesPostgresDateOffsets
{
    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function fromDateTime($value)
    {
        if (! empty($value) && $this->getConnection()->getDriverName() === 'pgsql') {
            // PostgreSQL must receive the offset for timestamp-with-time-zone columns.
            return $this->asDateTime($value)->format('Y-m-d H:i:sP');
        }

        return parent::fromDateTime($value);
    }
}
