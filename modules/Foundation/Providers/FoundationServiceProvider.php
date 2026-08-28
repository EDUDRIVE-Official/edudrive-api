<?php

declare(strict_types=1);

namespace Modules\Foundation\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email', '')).'|'.$request->ip()));

        RateLimiter::for('register', static fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->ip()));

        RateLimiter::for('activate', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));

        RateLimiter::for('public-verification', static fn (Request $request): Limit => Limit::perMinute(30)->by((string) $request->ip()));

        RateLimiter::for(
            'simulator-integration',
            static fn (Request $request): Limit => Limit::perMinute(60)
                ->by((string) ($request->attributes->get('authenticated_simulator_id') ?? $request->ip())),
        );
    }
}
