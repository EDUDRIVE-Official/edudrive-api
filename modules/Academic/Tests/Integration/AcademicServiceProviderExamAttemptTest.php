<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\UseCases\AnswerAttemptQuestionHandler;
use Modules\Academic\Application\UseCases\CancelExamAttemptHandler;
use Modules\Academic\Application\UseCases\GetExamAttemptHandler;
use Modules\Academic\Application\UseCases\ListExamAttemptsHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\SubmitExamAttemptHandler;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamAttemptRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

it('registra el repositorio y handlers de exam attempts en el contenedor y registry', function (): void {
    expect(app(ExamAttemptRepository::class))->toBeInstanceOf(EloquentExamAttemptRepository::class);

    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(StartExamAttemptCommand::class))->toBe(StartExamAttemptHandler::class)
        ->and($registry->handlerFor(AnswerAttemptQuestionCommand::class))->toBe(AnswerAttemptQuestionHandler::class)
        ->and($registry->handlerFor(SubmitExamAttemptCommand::class))->toBe(SubmitExamAttemptHandler::class)
        ->and($registry->handlerFor(CancelExamAttemptCommand::class))->toBe(CancelExamAttemptHandler::class)
        ->and($registry->handlerFor(GetExamAttemptQuery::class))->toBe(GetExamAttemptHandler::class)
        ->and($registry->handlerFor(ListExamAttemptsQuery::class))->toBe(ListExamAttemptsHandler::class);
});
