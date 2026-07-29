<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Foundation\Providers\FoundationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    FoundationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
];
