<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Commands\CreateBadgeCommand;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Commands\GrantBadgeCommand;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Commands\RetireBadgeCommand;
use Modules\Gamification\Application\Commands\UpdateBadgeCommand;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Queries\GetBadgeQuery;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\UseCases\CreateAchievementHandler;
use Modules\Gamification\Application\UseCases\CreateBadgeHandler;
use Modules\Gamification\Application\UseCases\GetAchievementHandler;
use Modules\Gamification\Application\UseCases\GetBadgeHandler;
use Modules\Gamification\Application\UseCases\GetMyAchievementsHandler;
use Modules\Gamification\Application\UseCases\GetMyBadgesHandler;
use Modules\Gamification\Application\UseCases\GrantAchievementHandler;
use Modules\Gamification\Application\UseCases\GrantBadgeHandler;
use Modules\Gamification\Application\UseCases\ListAchievementsHandler;
use Modules\Gamification\Application\UseCases\ListBadgesHandler;
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

final class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AchievementRepository::class, EloquentAchievementRepository::class);
        $this->app->bind(UserAchievementRepository::class, EloquentUserAchievementRepository::class);
        $this->app->bind(BadgeRepository::class, EloquentBadgeRepository::class);
        $this->app->bind(UserBadgeRepository::class, EloquentUserBadgeRepository::class);
        $this->app->bind(ExperienceEntryRepository::class, EloquentExperienceEntryRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(CreateAchievementCommand::class, CreateAchievementHandler::class);
        $registry->register(RetireAchievementCommand::class, RetireAchievementHandler::class);
        $registry->register(GrantAchievementCommand::class, GrantAchievementHandler::class);
        $registry->register(GetAchievementQuery::class, GetAchievementHandler::class);
        $registry->register(ListAchievementsQuery::class, ListAchievementsHandler::class);
        $registry->register(GetMyAchievementsQuery::class, GetMyAchievementsHandler::class);

        $registry->register(CreateBadgeCommand::class, CreateBadgeHandler::class);
        $registry->register(UpdateBadgeCommand::class, UpdateBadgeHandler::class);
        $registry->register(RetireBadgeCommand::class, RetireBadgeHandler::class);
        $registry->register(GrantBadgeCommand::class, GrantBadgeHandler::class);
        $registry->register(GetBadgeQuery::class, GetBadgeHandler::class);
        $registry->register(ListBadgesQuery::class, ListBadgesHandler::class);
        $registry->register(GetMyBadgesQuery::class, GetMyBadgesHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
