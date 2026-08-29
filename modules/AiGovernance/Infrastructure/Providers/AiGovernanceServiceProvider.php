<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AiGovernance\Application\Commands\ApproveAiDecisionCommand;
use Modules\AiGovernance\Application\Commands\ApproveAiModelCommand;
use Modules\AiGovernance\Application\Commands\ApproveAiPromptCommand;
use Modules\AiGovernance\Application\Commands\ApproveAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\ApproveAiSystemByCommitteeCommand;
use Modules\AiGovernance\Application\Commands\CreateAiPromptCommand;
use Modules\AiGovernance\Application\Commands\DeprecateAiModelCommand;
use Modules\AiGovernance\Application\Commands\GrantAiSystemExtraordinaryApprovalCommand;
use Modules\AiGovernance\Application\Commands\InvokeAiGatewayCommand;
use Modules\AiGovernance\Application\Commands\PromoteAiSystemCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiModelCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiSystemCommand;
use Modules\AiGovernance\Application\Commands\RejectAiDecisionCommand;
use Modules\AiGovernance\Application\Commands\RejectAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\ReportAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\RequireAiProviderReevaluationCommand;
use Modules\AiGovernance\Application\Commands\ResolveAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\RetireAiModelCommand;
use Modules\AiGovernance\Application\Commands\RetireAiPromptCommand;
use Modules\AiGovernance\Application\Commands\StartAiIncidentInvestigationCommand;
use Modules\AiGovernance\Application\Commands\UpdateAiPromptContentCommand;
use Modules\AiGovernance\Application\Queries\GetAiDecisionQuery;
use Modules\AiGovernance\Application\Queries\GetAiIncidentQuery;
use Modules\AiGovernance\Application\Queries\GetAiModelQuery;
use Modules\AiGovernance\Application\Queries\GetAiPromptQuery;
use Modules\AiGovernance\Application\Queries\GetAiProviderEvaluationQuery;
use Modules\AiGovernance\Application\Queries\GetAiSystemQuery;
use Modules\AiGovernance\Application\Queries\ListAiDecisionsQuery;
use Modules\AiGovernance\Application\Queries\ListAiIncidentsQuery;
use Modules\AiGovernance\Application\Queries\ListAiModelsQuery;
use Modules\AiGovernance\Application\Queries\ListAiPromptsQuery;
use Modules\AiGovernance\Application\Queries\ListAiProviderEvaluationsQuery;
use Modules\AiGovernance\Application\Queries\ListAiSystemsQuery;
use Modules\AiGovernance\Application\Services\AiGatewayClient;
use Modules\AiGovernance\Application\UseCases\ApproveAiDecisionHandler;
use Modules\AiGovernance\Application\UseCases\ApproveAiModelHandler;
use Modules\AiGovernance\Application\UseCases\ApproveAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\ApproveAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\ApproveAiSystemByCommitteeHandler;
use Modules\AiGovernance\Application\UseCases\CreateAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\DeprecateAiModelHandler;
use Modules\AiGovernance\Application\UseCases\GetAiDecisionHandler;
use Modules\AiGovernance\Application\UseCases\GetAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\GetAiModelHandler;
use Modules\AiGovernance\Application\UseCases\GetAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\GetAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\GetAiSystemHandler;
use Modules\AiGovernance\Application\UseCases\GrantAiSystemExtraordinaryApprovalHandler;
use Modules\AiGovernance\Application\UseCases\InvokeAiGatewayHandler;
use Modules\AiGovernance\Application\UseCases\ListAiDecisionsHandler;
use Modules\AiGovernance\Application\UseCases\ListAiIncidentsHandler;
use Modules\AiGovernance\Application\UseCases\ListAiModelsHandler;
use Modules\AiGovernance\Application\UseCases\ListAiPromptsHandler;
use Modules\AiGovernance\Application\UseCases\ListAiProviderEvaluationsHandler;
use Modules\AiGovernance\Application\UseCases\ListAiSystemsHandler;
use Modules\AiGovernance\Application\UseCases\PromoteAiSystemHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiModelHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiSystemHandler;
use Modules\AiGovernance\Application\UseCases\RejectAiDecisionHandler;
use Modules\AiGovernance\Application\UseCases\RejectAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\ReportAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\RequireAiProviderReevaluationHandler;
use Modules\AiGovernance\Application\UseCases\ResolveAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\RetireAiModelHandler;
use Modules\AiGovernance\Application\UseCases\RetireAiPromptHandler;
use Modules\AiGovernance\Application\UseCases\StartAiIncidentInvestigationHandler;
use Modules\AiGovernance\Application\UseCases\UpdateAiPromptContentHandler;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\Repositories\AiModelRepository;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiDecisionRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiIncidentRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiModelRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiPromptRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiProviderEvaluationRepository;
use Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Repositories\EloquentAiSystemRepository;
use Modules\AiGovernance\Infrastructure\Services\HttpAiGatewayClient;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AiGovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiSystemRepository::class, EloquentAiSystemRepository::class);
        $this->app->bind(AiDecisionRepository::class, EloquentAiDecisionRepository::class);
        $this->app->bind(AiModelRepository::class, EloquentAiModelRepository::class);
        $this->app->bind(AiPromptRepository::class, EloquentAiPromptRepository::class);
        $this->app->bind(AiIncidentRepository::class, EloquentAiIncidentRepository::class);
        $this->app->bind(AiProviderEvaluationRepository::class, EloquentAiProviderEvaluationRepository::class);
        $this->app->bind(AiGatewayClient::class, HttpAiGatewayClient::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(RegisterAiProviderEvaluationCommand::class, RegisterAiProviderEvaluationHandler::class);
        $registry->register(ApproveAiProviderEvaluationCommand::class, ApproveAiProviderEvaluationHandler::class);
        $registry->register(RejectAiProviderEvaluationCommand::class, RejectAiProviderEvaluationHandler::class);
        $registry->register(RequireAiProviderReevaluationCommand::class, RequireAiProviderReevaluationHandler::class);
        $registry->register(GetAiProviderEvaluationQuery::class, GetAiProviderEvaluationHandler::class);
        $registry->register(ListAiProviderEvaluationsQuery::class, ListAiProviderEvaluationsHandler::class);

        $registry->register(RegisterAiModelCommand::class, RegisterAiModelHandler::class);
        $registry->register(ApproveAiModelCommand::class, ApproveAiModelHandler::class);
        $registry->register(DeprecateAiModelCommand::class, DeprecateAiModelHandler::class);
        $registry->register(RetireAiModelCommand::class, RetireAiModelHandler::class);
        $registry->register(GetAiModelQuery::class, GetAiModelHandler::class);
        $registry->register(ListAiModelsQuery::class, ListAiModelsHandler::class);

        $registry->register(CreateAiPromptCommand::class, CreateAiPromptHandler::class);
        $registry->register(UpdateAiPromptContentCommand::class, UpdateAiPromptContentHandler::class);
        $registry->register(ApproveAiPromptCommand::class, ApproveAiPromptHandler::class);
        $registry->register(RetireAiPromptCommand::class, RetireAiPromptHandler::class);
        $registry->register(GetAiPromptQuery::class, GetAiPromptHandler::class);
        $registry->register(ListAiPromptsQuery::class, ListAiPromptsHandler::class);

        $registry->register(RegisterAiSystemCommand::class, RegisterAiSystemHandler::class);
        $registry->register(PromoteAiSystemCommand::class, PromoteAiSystemHandler::class);
        $registry->register(GrantAiSystemExtraordinaryApprovalCommand::class, GrantAiSystemExtraordinaryApprovalHandler::class);
        $registry->register(ApproveAiSystemByCommitteeCommand::class, ApproveAiSystemByCommitteeHandler::class);
        $registry->register(GetAiSystemQuery::class, GetAiSystemHandler::class);
        $registry->register(ListAiSystemsQuery::class, ListAiSystemsHandler::class);

        $registry->register(ApproveAiDecisionCommand::class, ApproveAiDecisionHandler::class);
        $registry->register(RejectAiDecisionCommand::class, RejectAiDecisionHandler::class);
        $registry->register(GetAiDecisionQuery::class, GetAiDecisionHandler::class);
        $registry->register(ListAiDecisionsQuery::class, ListAiDecisionsHandler::class);

        $registry->register(ReportAiIncidentCommand::class, ReportAiIncidentHandler::class);
        $registry->register(StartAiIncidentInvestigationCommand::class, StartAiIncidentInvestigationHandler::class);
        $registry->register(ResolveAiIncidentCommand::class, ResolveAiIncidentHandler::class);
        $registry->register(GetAiIncidentQuery::class, GetAiIncidentHandler::class);
        $registry->register(ListAiIncidentsQuery::class, ListAiIncidentsHandler::class);

        $registry->register(InvokeAiGatewayCommand::class, InvokeAiGatewayHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
