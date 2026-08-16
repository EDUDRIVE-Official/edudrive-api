<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Learning\Application\Services\LearningEventRecorder;
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

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
