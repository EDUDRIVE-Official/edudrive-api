<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\RoadPassport\Application\Commands\ChangeRoadPassportLevelCommand;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Queries\GetRoadPassportQuery;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Application\UseCases\ChangeRoadPassportLevelHandler;
use Modules\RoadPassport\Application\UseCases\GetMyRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\GetRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\IssueRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\ReactivateRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\RevokeRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\SuspendRoadPassportHandler;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoadPassportRepository;
use Modules\RoadPassport\Infrastructure\Services\DefaultRoadPassportEvidenceRecorder;

final class RoadPassportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoadPassportRepository::class, EloquentRoadPassportRepository::class);
        $this->app->bind(RoadPassportEvidenceRecorder::class, DefaultRoadPassportEvidenceRecorder::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(IssueRoadPassportCommand::class, IssueRoadPassportHandler::class);
        $registry->register(SuspendRoadPassportCommand::class, SuspendRoadPassportHandler::class);
        $registry->register(ReactivateRoadPassportCommand::class, ReactivateRoadPassportHandler::class);
        $registry->register(RevokeRoadPassportCommand::class, RevokeRoadPassportHandler::class);
        $registry->register(ChangeRoadPassportLevelCommand::class, ChangeRoadPassportLevelHandler::class);
        $registry->register(GetRoadPassportQuery::class, GetRoadPassportHandler::class);
        $registry->register(GetMyRoadPassportQuery::class, GetMyRoadPassportHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
