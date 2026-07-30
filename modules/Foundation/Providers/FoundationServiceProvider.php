<?php

declare(strict_types=1);

namespace Modules\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Infrastructure\Bus\InMemoryMessageHandlerRegistry;
use Modules\Foundation\Infrastructure\Bus\LaravelCommandBus;
use Modules\Foundation\Infrastructure\Bus\LaravelQueryBus;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MessageHandlerRegistry::class,
            InMemoryMessageHandlerRegistry::class,
        );

        $this->app->singleton(
            CommandBus::class,
            LaravelCommandBus::class,
        );

        $this->app->singleton(
            QueryBus::class,
            LaravelQueryBus::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
