<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\StartTheoryExamSimulationCommand;
use Modules\Academic\Application\Queries\GetTheoryExamQuery;
use Modules\Academic\Application\Queries\ListTheoryExamAttemptsQuery;
use Modules\Academic\Application\Queries\ListTheoryExamsQuery;
use Modules\Academic\Application\UseCases\GetTheoryExamHandler;
use Modules\Academic\Application\UseCases\ListTheoryExamAttemptsHandler;
use Modules\Academic\Application\UseCases\ListTheoryExamsHandler;
use Modules\Academic\Application\UseCases\StartTheoryExamSimulationHandler;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

it('registra handlers de theory exams y theory attempts en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(ListTheoryExamsQuery::class))->toBe(ListTheoryExamsHandler::class)
        ->and($registry->handlerFor(GetTheoryExamQuery::class))->toBe(GetTheoryExamHandler::class)
        ->and($registry->handlerFor(StartTheoryExamSimulationCommand::class))->toBe(StartTheoryExamSimulationHandler::class)
        ->and($registry->handlerFor(ListTheoryExamAttemptsQuery::class))->toBe(ListTheoryExamAttemptsHandler::class);
});
