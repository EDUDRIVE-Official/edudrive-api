<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\UseCases\CreateEnrollmentHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentHandler;
use Modules\Academic\Application\UseCases\ListEnrollmentsHandler;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

it('registra handlers CQRS de enrollment individual en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CreateEnrollmentCommand::class))->toBe(CreateEnrollmentHandler::class)
        ->and($registry->handlerFor(GetEnrollmentQuery::class))->toBe(GetEnrollmentHandler::class)
        ->and($registry->handlerFor(ListEnrollmentsQuery::class))->toBe(ListEnrollmentsHandler::class);
});
