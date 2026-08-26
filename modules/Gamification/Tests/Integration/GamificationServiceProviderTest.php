<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\UseCases\CreateAchievementHandler;
use Modules\Gamification\Application\UseCases\GetAchievementHandler;
use Modules\Gamification\Application\UseCases\GetMyAchievementsHandler;
use Modules\Gamification\Application\UseCases\GrantAchievementHandler;
use Modules\Gamification\Application\UseCases\ListAchievementsHandler;
use Modules\Gamification\Application\UseCases\RetireAchievementHandler;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserAchievementRepository;

it('registra los repositorios de logros en el contenedor', function (): void {
    expect(app(AchievementRepository::class))->toBeInstanceOf(EloquentAchievementRepository::class)
        ->and(app(UserAchievementRepository::class))->toBeInstanceOf(EloquentUserAchievementRepository::class);
});

it('registra los handlers CQRS de logros en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CreateAchievementCommand::class))->toBe(CreateAchievementHandler::class)
        ->and($registry->handlerFor(RetireAchievementCommand::class))->toBe(RetireAchievementHandler::class)
        ->and($registry->handlerFor(GrantAchievementCommand::class))->toBe(GrantAchievementHandler::class)
        ->and($registry->handlerFor(GetAchievementQuery::class))->toBe(GetAchievementHandler::class)
        ->and($registry->handlerFor(ListAchievementsQuery::class))->toBe(ListAchievementsHandler::class)
        ->and($registry->handlerFor(GetMyAchievementsQuery::class))->toBe(GetMyAchievementsHandler::class);
});
