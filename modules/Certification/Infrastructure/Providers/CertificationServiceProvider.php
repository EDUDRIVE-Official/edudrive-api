<?php

declare(strict_types=1);

namespace Modules\Certification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Certification\Application\Commands\IssueCertificateCommand;
use Modules\Certification\Application\Commands\RevokeCertificateCommand;
use Modules\Certification\Application\Queries\GetCertificateQuery;
use Modules\Certification\Application\Queries\GetMyCertificatesQuery;
use Modules\Certification\Application\UseCases\GetCertificateHandler;
use Modules\Certification\Application\UseCases\GetMyCertificatesHandler;
use Modules\Certification\Application\UseCases\IssueCertificateHandler;
use Modules\Certification\Application\UseCases\RevokeCertificateHandler;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Infrastructure\Persistence\Eloquent\Repositories\EloquentCertificateRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class CertificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CertificateRepository::class, EloquentCertificateRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(IssueCertificateCommand::class, IssueCertificateHandler::class);
        $registry->register(RevokeCertificateCommand::class, RevokeCertificateHandler::class);
        $registry->register(GetCertificateQuery::class, GetCertificateHandler::class);
        $registry->register(GetMyCertificatesQuery::class, GetMyCertificatesHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
