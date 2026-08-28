<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Academic\Application\Commands\ActivateEnrollmentCommand;
use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveProgramCommand;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Application\Commands\BulkImportQuestionsCommand;
use Modules\Academic\Application\Commands\CancelEnrollmentCommand;
use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Commands\CompleteEnrollmentCommand;
use Modules\Academic\Application\Commands\CompleteLessonCommand;
use Modules\Academic\Application\Commands\CreateBulkEnrollmentsCommand;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Commands\CreateExamCommand;
use Modules\Academic\Application\Commands\CreateInstitutionalEnrollmentCommand;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Commands\CreateQuestionCommand;
use Modules\Academic\Application\Commands\DeleteExamCommand;
use Modules\Academic\Application\Commands\DeleteQuestionCommand;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Application\Commands\ExportEnrollmentsCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\PublishProgramCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\Commands\SendCourseBackToDraftCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\StartTheoryExamSimulationCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Commands\UpdateExamCommand;
use Modules\Academic\Application\Commands\UpdateQuestionCommand;
use Modules\Academic\Application\Queries\GetCourseActivityReportQuery;
use Modules\Academic\Application\Queries\GetCourseApprovalReportQuery;
use Modules\Academic\Application\Queries\GetCourseCompetencyReportQuery;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Queries\GetCoursePerformanceReportQuery;
use Modules\Academic\Application\Queries\GetCourseProgressReportQuery;
use Modules\Academic\Application\Queries\GetCourseVersionQuery;
use Modules\Academic\Application\Queries\GetEnrollmentCurriculumStatusQuery;
use Modules\Academic\Application\Queries\GetEnrollmentLearningRecommendationsQuery;
use Modules\Academic\Application\Queries\GetEnrollmentProgressQuery;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Queries\GetQuestionQuery;
use Modules\Academic\Application\Queries\GetTheoryExamQuery;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Queries\ListCompetenciesQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Queries\ListCourseVersionsQuery;
use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Queries\ListExamsQuery;
use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\Queries\ListQuestionsQuery;
use Modules\Academic\Application\Queries\ListTheoryExamAttemptsQuery;
use Modules\Academic\Application\Queries\ListTheoryExamsQuery;
use Modules\Academic\Application\UseCases\ActivateEnrollmentHandler;
use Modules\Academic\Application\UseCases\AddCompetencyIndicatorHandler;
use Modules\Academic\Application\UseCases\AddSubcompetencyHandler;
use Modules\Academic\Application\UseCases\AnswerAttemptQuestionHandler;
use Modules\Academic\Application\UseCases\ApproveCourseHandler;
use Modules\Academic\Application\UseCases\ArchiveCourseHandler;
use Modules\Academic\Application\UseCases\ArchiveProgramHandler;
use Modules\Academic\Application\UseCases\BulkImportCoursesHandler;
use Modules\Academic\Application\UseCases\BulkImportQuestionsHandler;
use Modules\Academic\Application\UseCases\CancelEnrollmentHandler;
use Modules\Academic\Application\UseCases\CancelExamAttemptHandler;
use Modules\Academic\Application\UseCases\ChangeProgramAudienceHandler;
use Modules\Academic\Application\UseCases\CompleteEnrollmentHandler;
use Modules\Academic\Application\UseCases\CompleteLessonHandler;
use Modules\Academic\Application\UseCases\CreateBulkEnrollmentsHandler;
use Modules\Academic\Application\UseCases\CreateCompetencyHandler;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Application\UseCases\CreateEnrollmentHandler;
use Modules\Academic\Application\UseCases\CreateExamHandler;
use Modules\Academic\Application\UseCases\CreateInstitutionalEnrollmentHandler;
use Modules\Academic\Application\UseCases\CreateProgramHandler;
use Modules\Academic\Application\UseCases\CreateQuestionHandler;
use Modules\Academic\Application\UseCases\DeleteExamHandler;
use Modules\Academic\Application\UseCases\DeleteQuestionHandler;
use Modules\Academic\Application\UseCases\ExportCoursesHandler;
use Modules\Academic\Application\UseCases\ExportEnrollmentsHandler;
use Modules\Academic\Application\UseCases\GetCourseActivityReportHandler;
use Modules\Academic\Application\UseCases\GetCourseApprovalReportHandler;
use Modules\Academic\Application\UseCases\GetCourseCompetencyReportHandler;
use Modules\Academic\Application\UseCases\GetCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\GetCoursePerformanceReportHandler;
use Modules\Academic\Application\UseCases\GetCourseProgressReportHandler;
use Modules\Academic\Application\UseCases\GetCourseVersionHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentCurriculumStatusHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentLearningRecommendationsHandler;
use Modules\Academic\Application\UseCases\GetEnrollmentProgressHandler;
use Modules\Academic\Application\UseCases\GetExamAttemptHandler;
use Modules\Academic\Application\UseCases\GetExamHandler;
use Modules\Academic\Application\UseCases\GetQuestionHandler;
use Modules\Academic\Application\UseCases\GetTheoryExamHandler;
use Modules\Academic\Application\UseCases\GetUnitContentHandler;
use Modules\Academic\Application\UseCases\ListCompetenciesHandler;
use Modules\Academic\Application\UseCases\ListCoursesHandler;
use Modules\Academic\Application\UseCases\ListCourseVersionsHandler;
use Modules\Academic\Application\UseCases\ListEnrollmentsHandler;
use Modules\Academic\Application\UseCases\ListExamAttemptsHandler;
use Modules\Academic\Application\UseCases\ListExamsHandler;
use Modules\Academic\Application\UseCases\ListProgramsHandler;
use Modules\Academic\Application\UseCases\ListQuestionsHandler;
use Modules\Academic\Application\UseCases\ListTheoryExamAttemptsHandler;
use Modules\Academic\Application\UseCases\ListTheoryExamsHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\PublishProgramHandler;
use Modules\Academic\Application\UseCases\ReopenCourseHandler;
use Modules\Academic\Application\UseCases\ReplaceCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\ReplaceProgramCoursesHandler;
use Modules\Academic\Application\UseCases\ReplaceUnitContentHandler;
use Modules\Academic\Application\UseCases\SendCourseBackToDraftHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\StartTheoryExamSimulationHandler;
use Modules\Academic\Application\UseCases\SubmitCourseForReviewHandler;
use Modules\Academic\Application\UseCases\SubmitExamAttemptHandler;
use Modules\Academic\Application\UseCases\UpdateExamHandler;
use Modules\Academic\Application\UseCases\UpdateQuestionHandler;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\Repositories\EnrollmentProgressRepository;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCompetencyRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentCourseVersionRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentProgressRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentEnrollmentRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamAttemptRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamRepository;
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
            EnrollmentRepository::class,
            EloquentEnrollmentRepository::class,
        );

        $this->app->bind(
            EnrollmentProgressRepository::class,
            EloquentEnrollmentProgressRepository::class,
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

        $this->app->bind(
            ExamRepository::class,
            EloquentExamRepository::class,
        );

        $this->app->bind(
            ExamAttemptRepository::class,
            EloquentExamAttemptRepository::class,
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
            BulkImportCoursesCommand::class,
            BulkImportCoursesHandler::class,
        );

        $registry->register(ExportCoursesCommand::class, ExportCoursesHandler::class);
        $registry->register(ExportEnrollmentsCommand::class, ExportEnrollmentsHandler::class);

        $registry->register(GetCourseProgressReportQuery::class, GetCourseProgressReportHandler::class);
        $registry->register(GetCoursePerformanceReportQuery::class, GetCoursePerformanceReportHandler::class);
        $registry->register(GetCourseApprovalReportQuery::class, GetCourseApprovalReportHandler::class);
        $registry->register(GetCourseCompetencyReportQuery::class, GetCourseCompetencyReportHandler::class);
        $registry->register(GetCourseActivityReportQuery::class, GetCourseActivityReportHandler::class);

        $registry->register(
            CreateEnrollmentCommand::class,
            CreateEnrollmentHandler::class,
        );

        $registry->register(
            CreateBulkEnrollmentsCommand::class,
            CreateBulkEnrollmentsHandler::class,
        );

        $registry->register(
            CreateInstitutionalEnrollmentCommand::class,
            CreateInstitutionalEnrollmentHandler::class,
        );

        $registry->register(
            ActivateEnrollmentCommand::class,
            ActivateEnrollmentHandler::class,
        );

        $registry->register(
            CompleteEnrollmentCommand::class,
            CompleteEnrollmentHandler::class,
        );

        $registry->register(
            CancelEnrollmentCommand::class,
            CancelEnrollmentHandler::class,
        );

        $registry->register(
            GetEnrollmentQuery::class,
            GetEnrollmentHandler::class,
        );

        $registry->register(
            ListEnrollmentsQuery::class,
            ListEnrollmentsHandler::class,
        );

        $registry->register(
            CompleteLessonCommand::class,
            CompleteLessonHandler::class,
        );

        $registry->register(
            GetEnrollmentProgressQuery::class,
            GetEnrollmentProgressHandler::class,
        );

        $registry->register(
            GetEnrollmentCurriculumStatusQuery::class,
            GetEnrollmentCurriculumStatusHandler::class,
        );

        $registry->register(
            GetEnrollmentLearningRecommendationsQuery::class,
            GetEnrollmentLearningRecommendationsHandler::class,
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
        $registry->register(BulkImportQuestionsCommand::class, BulkImportQuestionsHandler::class);
        $registry->register(UpdateQuestionCommand::class, UpdateQuestionHandler::class);
        $registry->register(DeleteQuestionCommand::class, DeleteQuestionHandler::class);
        $registry->register(GetQuestionQuery::class, GetQuestionHandler::class);
        $registry->register(ListQuestionsQuery::class, ListQuestionsHandler::class);

        $registry->register(CreateExamCommand::class, CreateExamHandler::class);
        $registry->register(UpdateExamCommand::class, UpdateExamHandler::class);
        $registry->register(DeleteExamCommand::class, DeleteExamHandler::class);
        $registry->register(GetExamQuery::class, GetExamHandler::class);
        $registry->register(ListExamsQuery::class, ListExamsHandler::class);
        $registry->register(StartExamAttemptCommand::class, StartExamAttemptHandler::class);
        $registry->register(StartTheoryExamSimulationCommand::class, StartTheoryExamSimulationHandler::class);
        $registry->register(AnswerAttemptQuestionCommand::class, AnswerAttemptQuestionHandler::class);
        $registry->register(SubmitExamAttemptCommand::class, SubmitExamAttemptHandler::class);
        $registry->register(CancelExamAttemptCommand::class, CancelExamAttemptHandler::class);
        $registry->register(GetExamAttemptQuery::class, GetExamAttemptHandler::class);
        $registry->register(ListExamAttemptsQuery::class, ListExamAttemptsHandler::class);
        $registry->register(ListTheoryExamsQuery::class, ListTheoryExamsHandler::class);
        $registry->register(GetTheoryExamQuery::class, GetTheoryExamHandler::class);
        $registry->register(ListTheoryExamAttemptsQuery::class, ListTheoryExamAttemptsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
