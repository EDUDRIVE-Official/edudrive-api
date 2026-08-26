<?php

declare(strict_types=1);

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

it('registra el repositorio de certificados en el contenedor', function (): void {
    expect(app(CertificateRepository::class))->toBeInstanceOf(EloquentCertificateRepository::class);
});

it('registra los handlers CQRS de certificados en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(IssueCertificateCommand::class))->toBe(IssueCertificateHandler::class)
        ->and($registry->handlerFor(RevokeCertificateCommand::class))->toBe(RevokeCertificateHandler::class)
        ->and($registry->handlerFor(GetCertificateQuery::class))->toBe(GetCertificateHandler::class)
        ->and($registry->handlerFor(GetMyCertificatesQuery::class))->toBe(GetMyCertificatesHandler::class);
});
