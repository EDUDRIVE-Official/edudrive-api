<?php

declare(strict_types=1);

use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Commands\CreateBadgeCommand;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Commands\GrantBadgeCommand;
use Modules\Gamification\Application\Commands\RecordExperienceCommand;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Commands\RetireBadgeCommand;
use Modules\Gamification\Application\Commands\UpdateBadgeCommand;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Queries\GetBadgeQuery;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Queries\GetMyExperienceSummaryQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\UseCases\CreateAchievementHandler;
use Modules\Gamification\Application\UseCases\CreateBadgeHandler;
use Modules\Gamification\Application\UseCases\GetAchievementHandler;
use Modules\Gamification\Application\UseCases\GetBadgeHandler;
use Modules\Gamification\Application\UseCases\GetMyAchievementsHandler;
use Modules\Gamification\Application\UseCases\GetMyBadgesHandler;
use Modules\Gamification\Application\UseCases\GetMyExperienceSummaryHandler;
use Modules\Gamification\Application\UseCases\GrantAchievementHandler;
use Modules\Gamification\Application\UseCases\GrantBadgeHandler;
use Modules\Gamification\Application\UseCases\ListAchievementsHandler;
use Modules\Gamification\Application\UseCases\ListBadgesHandler;
use Modules\Gamification\Application\UseCases\RecordExperienceHandler;
use Modules\Gamification\Application\UseCases\RetireAchievementHandler;
use Modules\Gamification\Application\UseCases\RetireBadgeHandler;
use Modules\Gamification\Application\UseCases\UpdateBadgeHandler;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentBadgeRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentExperienceEntryRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserAchievementRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserBadgeRepository;

it('registra los repositorios de logros en el contenedor', function (): void {
    expect(app(AchievementRepository::class))->toBeInstanceOf(EloquentAchievementRepository::class)
        ->and(app(UserAchievementRepository::class))->toBeInstanceOf(EloquentUserAchievementRepository::class);
});

it('registra los repositorios de insignias en el contenedor', function (): void {
    expect(app(BadgeRepository::class))->toBeInstanceOf(EloquentBadgeRepository::class)
        ->and(app(UserBadgeRepository::class))->toBeInstanceOf(EloquentUserBadgeRepository::class);
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

it('registra los handlers CQRS de insignias en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(CreateBadgeCommand::class))->toBe(CreateBadgeHandler::class)
        ->and($registry->handlerFor(UpdateBadgeCommand::class))->toBe(UpdateBadgeHandler::class)
        ->and($registry->handlerFor(RetireBadgeCommand::class))->toBe(RetireBadgeHandler::class)
        ->and($registry->handlerFor(GrantBadgeCommand::class))->toBe(GrantBadgeHandler::class)
        ->and($registry->handlerFor(GetBadgeQuery::class))->toBe(GetBadgeHandler::class)
        ->and($registry->handlerFor(ListBadgesQuery::class))->toBe(ListBadgesHandler::class)
        ->and($registry->handlerFor(GetMyBadgesQuery::class))->toBe(GetMyBadgesHandler::class);
});

it('registra el repositorio de experiencia en el contenedor', function (): void {
    expect(app(ExperienceEntryRepository::class))->toBeInstanceOf(EloquentExperienceEntryRepository::class);
});

it('registra los handlers CQRS de experiencia en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(RecordExperienceCommand::class))->toBe(RecordExperienceHandler::class)
        ->and($registry->handlerFor(GetMyExperienceSummaryQuery::class))->toBe(GetMyExperienceSummaryHandler::class);
});
