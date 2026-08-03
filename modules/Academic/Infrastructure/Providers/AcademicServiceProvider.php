<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Queries\ListCompetenciesQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\UseCases\AddCompetencyIndicatorHandler;
use Modules\Academic\Application\UseCases\AddSubcompetencyHandler;
use Modules\Academic\Application\UseCases\ArchiveCourseHandler;
use Modules\Academic\Application\UseCases\ChangeProgramAudienceHandler;
use Modules\Academic\Application\UseCases\CreateCompetencyHandler;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Application\UseCases\CreateProgramHandler;
use Modules\Academic\Application\UseCases\ListCompetenciesHandler;
use Modules\Academic\Application\UseCases\ListCoursesHandler;
use Modules\Academic\Application\UseCases\ListProgramsHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\ReplaceProgramCoursesHandler;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCompetencyRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentProgramRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AcademicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompetencyRepository::class,
            EloquentCompetencyRepository::class,
        );

        $this->app->bind(
            CourseRepository::class,
            EloquentCourseRepository::class,
        );

        $this->app->bind(
            ProgramRepository::class,
            EloquentProgramRepository::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(CreateCompetencyCommand::class, CreateCompetencyHandler::class);
        $registry->register(AddSubcompetencyCommand::class, AddSubcompetencyHandler::class);
        $registry->register(AddCompetencyIndicatorCommand::class, AddCompetencyIndicatorHandler::class);
        $registry->register(ListCompetenciesQuery::class, ListCompetenciesHandler::class);
        $registry->register(CreateProgramCommand::class, CreateProgramHandler::class);
        $registry->register(ListProgramsQuery::class, ListProgramsHandler::class);
        $registry->register(ChangeProgramAudienceCommand::class, ChangeProgramAudienceHandler::class);
        $registry->register(ReplaceProgramCoursesCommand::class, ReplaceProgramCoursesHandler::class);

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
