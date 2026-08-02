<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\UseCases\ArchiveCourseHandler;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Application\UseCases\ListCoursesHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AcademicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CourseRepository::class,
            EloquentCourseRepository::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(
            CreateCourseCommand::class,
            CreateCourseHandler::class,
        );

        $registry->register(
            PublishCourseCommand::class,
            PublishCourseHandler::class,
        );

        $registry->register(
            ArchiveCourseCommand::class,
            ArchiveCourseHandler::class,
        );

        $registry->register(
            ListCoursesQuery::class,
            ListCoursesHandler::class,
        );

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
