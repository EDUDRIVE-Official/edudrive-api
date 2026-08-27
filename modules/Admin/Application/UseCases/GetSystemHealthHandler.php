<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Responses\SystemHealthResponse;
use Throwable;

final readonly class GetSystemHealthHandler
{
    public function handle(GetSystemHealthQuery $query): SystemHealthResponse
    {
        return SystemHealthResponse::fromDatabaseUp(
            databaseUp: $this->isDatabaseUp(),
            checkedAt: new DateTimeImmutable('now'),
        );
    }

    private function isDatabaseUp(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
