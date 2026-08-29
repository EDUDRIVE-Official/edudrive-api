<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Academic\Infrastructure\Providers\AcademicServiceProvider;
use Modules\Admin\Infrastructure\Providers\AdminServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Authorization\Infrastructure\Providers\AuthorizationServiceProvider;
use Modules\Certification\Infrastructure\Providers\CertificationServiceProvider;
use Modules\FileStorage\Infrastructure\Providers\FileStorageServiceProvider;
use Modules\Foundation\Providers\FoundationServiceProvider;
use Modules\Gamification\Infrastructure\Providers\GamificationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Integration\Infrastructure\Providers\IntegrationServiceProvider;
use Modules\Learning\Infrastructure\Providers\LearningServiceProvider;
use Modules\Legal\Infrastructure\Providers\LegalServiceProvider;
use Modules\Mobile\Infrastructure\Providers\MobileServiceProvider;
use Modules\Notification\Infrastructure\Providers\NotificationServiceProvider;
use Modules\Organization\Infrastructure\Providers\OrganizationServiceProvider;
use Modules\RoadPassport\Infrastructure\Providers\RoadPassportServiceProvider;
use Modules\Simulation\Infrastructure\Providers\SimulationServiceProvider;
use Modules\Webhook\Infrastructure\Providers\WebhookServiceProvider;

return [
    AppServiceProvider::class,
    FoundationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    AcademicServiceProvider::class,
    LearningServiceProvider::class,
    LegalServiceProvider::class,
    OrganizationServiceProvider::class,
    AuthorizationServiceProvider::class,
    RoadPassportServiceProvider::class,
    CertificationServiceProvider::class,
    SimulationServiceProvider::class,
    GamificationServiceProvider::class,
    NotificationServiceProvider::class,
    AdminServiceProvider::class,
    FileStorageServiceProvider::class,
    IntegrationServiceProvider::class,
    WebhookServiceProvider::class,
    MobileServiceProvider::class,
];
