<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Learning\Application\Queries\GetEnrollmentLearningEventsQuery;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Application\UseCases\GetEnrollmentLearningEventsHandler;
use Modules\Learning\Domain\Repositories\LearningEventRepository;
use Modules\Learning\Infrastructure\Persistence\Eloquent\Repositories\EloquentLearningEventRepository;
use Modules\Learning\Infrastructure\Services\DefaultLearningEventRecorder;

final class LearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LearningEventRepository::class, EloquentLearningEventRepository::class);
        $this->app->bind(LearningEventRecorder::class, DefaultLearningEventRecorder::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(
            GetEnrollmentLearningEventsQuery::class,
            GetEnrollmentLearningEventsHandler::class,
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
