<?php

declare(strict_types=1);

namespace Modules\Legal\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\UseCases\GetMyConsentsHandler;
use Modules\Legal\Application\UseCases\ListPoliciesHandler;
use Modules\Legal\Application\UseCases\PublishPolicyVersionHandler;
use Modules\Legal\Application\UseCases\RecordConsentHandler;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Infrastructure\Persistence\Eloquent\Repositories\EloquentConsentPolicyRepository;
use Modules\Legal\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserConsentRepository;

final class LegalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsentPolicyRepository::class, EloquentConsentPolicyRepository::class);
        $this->app->bind(UserConsentRepository::class, EloquentUserConsentRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(PublishPolicyVersionCommand::class, PublishPolicyVersionHandler::class);
        $registry->register(RecordConsentCommand::class, RecordConsentHandler::class);
        $registry->register(ListPoliciesQuery::class, ListPoliciesHandler::class);
        $registry->register(GetMyConsentsQuery::class, GetMyConsentsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
