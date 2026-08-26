<?php

declare(strict_types=1);

namespace Modules\Certification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Repositories\EloquentCertificateRepository;

final class CertificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CertificateRepository::class, EloquentCertificateRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
