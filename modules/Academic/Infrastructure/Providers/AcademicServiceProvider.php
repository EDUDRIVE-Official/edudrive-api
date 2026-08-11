<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveProgramCommand;
use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Commands\CreateQuestionCommand;
use Modules\Academic\Application\Commands\DeleteQuestionCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\PublishProgramCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\Commands\SendCourseBackToDraftCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Commands\UpdateQuestionCommand;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Queries\GetCourseVersionQuery;
use Modules\Academic\Application\Queries\GetQuestionQuery;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Queries\ListCompetenciesQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Queries\ListCourseVersionsQuery;
use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\Queries\ListQuestionsQuery;
use Modules\Academic\Application\UseCases\AddCompetencyIndicatorHandler;
use Modules\Academic\Application\UseCases\AddSubcompetencyHandler;
use Modules\Academic\Application\UseCases\ApproveCourseHandler;
use Modules\Academic\Application\UseCases\ArchiveCourseHandler;
use Modules\Academic\Application\UseCases\ArchiveProgramHandler;
use Modules\Academic\Application\UseCases\ChangeProgramAudienceHandler;
use Modules\Academic\Application\UseCases\CreateCompetencyHandler;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Application\UseCases\CreateProgramHandler;
use Modules\Academic\Application\UseCases\CreateQuestionHandler;
use Modules\Academic\Application\UseCases\DeleteQuestionHandler;
use Modules\Academic\Application\UseCases\GetCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\GetCourseVersionHandler;
use Modules\Academic\Application\UseCases\GetQuestionHandler;
use Modules\Academic\Application\UseCases\GetUnitContentHandler;
use Modules\Academic\Application\UseCases\ListCompetenciesHandler;
use Modules\Academic\Application\UseCases\ListCoursesHandler;
use Modules\Academic\Application\UseCases\ListCourseVersionsHandler;
use Modules\Academic\Application\UseCases\ListProgramsHandler;
use Modules\Academic\Application\UseCases\ListQuestionsHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\PublishProgramHandler;
use Modules\Academic\Application\UseCases\ReopenCourseHandler;
use Modules\Academic\Application\UseCases\ReplaceCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\ReplaceProgramCoursesHandler;
use Modules\Academic\Application\UseCases\ReplaceUnitContentHandler;
use Modules\Academic\Application\UseCases\SendCourseBackToDraftHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Application\UseCases\UpdateQuestionHandler;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCompetencyRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseVersionRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentProgramRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentQuestionRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentUnitContentRepository;
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
            CourseVersionRepository::class,
            EloquentCourseVersionRepository::class,
        );

        $this->app->bind(
            ProgramRepository::class,
            EloquentProgramRepository::class,
        );

        $this->app->bind(UnitContentRepository::class, EloquentUnitContentRepository::class);

        $this->app->bind(
            QuestionRepository::class,
            EloquentQuestionRepository::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(CreateCompetencyCommand::class, CreateCompetencyHandler::class);
        $registry->register(AddSubcompetencyCommand::class, AddSubcompetencyHandler::class);
        $registry->register(AddCompetencyIndicatorCommand::class, AddCompetencyIndicatorHandler::class);
        $registry->register(ListCompetenciesQuery::class, ListCompetenciesHandler::class);
        $registry->register(ReplaceCourseCurriculumCommand::class, ReplaceCourseCurriculumHandler::class);
        $registry->register(GetCourseCurriculumQuery::class, GetCourseCurriculumHandler::class);
        $registry->register(ReplaceUnitContentCommand::class, ReplaceUnitContentHandler::class);
        $registry->register(GetUnitContentQuery::class, GetUnitContentHandler::class);
        $registry->register(CreateProgramCommand::class, CreateProgramHandler::class);
        $registry->register(ListProgramsQuery::class, ListProgramsHandler::class);
        $registry->register(ChangeProgramAudienceCommand::class, ChangeProgramAudienceHandler::class);
        $registry->register(ReplaceProgramCoursesCommand::class, ReplaceProgramCoursesHandler::class);
        $registry->register(PublishProgramCommand::class, PublishProgramHandler::class);
        $registry->register(ArchiveProgramCommand::class, ArchiveProgramHandler::class);

        $registry->register(
            CreateCourseCommand::class,
            CreateCourseHandler::class,
        );

        $registry->register(
            PublishCourseCommand::class,
            PublishCourseHandler::class,
        );

        $registry->register(
            SubmitCourseForReviewCommand::class,
            SubmitCourseForReviewHandler::class,
        );

        $registry->register(
            ApproveCourseCommand::class,
            ApproveCourseHandler::class,
        );

        $registry->register(
            SendCourseBackToDraftCommand::class,
            SendCourseBackToDraftHandler::class,
        );

        $registry->register(
            ReopenCourseCommand::class,
            ReopenCourseHandler::class,
        );

        $registry->register(
            ListCourseVersionsQuery::class,
            ListCourseVersionsHandler::class,
        );

        $registry->register(
            GetCourseVersionQuery::class,
            GetCourseVersionHandler::class,
        );

        $registry->register(
            ArchiveCourseCommand::class,
            ArchiveCourseHandler::class,
        );

        $registry->register(
            ListCoursesQuery::class,
            ListCoursesHandler::class,
        );

        $registry->register(CreateQuestionCommand::class, CreateQuestionHandler::class);
        $registry->register(UpdateQuestionCommand::class, UpdateQuestionHandler::class);
        $registry->register(DeleteQuestionCommand::class, DeleteQuestionHandler::class);
        $registry->register(GetQuestionQuery::class, GetQuestionHandler::class);
        $registry->register(ListQuestionsQuery::class, ListQuestionsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
