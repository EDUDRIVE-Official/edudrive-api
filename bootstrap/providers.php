<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Academic\Infrastructure\Providers\AcademicServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Authorization\Infrastructure\Providers\AuthorizationServiceProvider;
use Modules\Foundation\Providers\FoundationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Organization\Infrastructure\Providers\OrganizationServiceProvider;

return [
    AppServiceProvider::class,
    FoundationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    AcademicServiceProvider::class,
    OrganizationServiceProvider::class,
    AuthorizationServiceProvider::class,
];
