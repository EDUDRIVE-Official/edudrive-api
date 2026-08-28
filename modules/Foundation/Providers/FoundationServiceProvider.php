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
use Modules\Foundation\Infrastructure\Environment\RequiredSecretsValidator;
use Modules\Foundation\Presentation\Console\ScanForSecretsCommand;

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
        if ($this->app->environment('production')) {
            $connection = (string) config('database.default');

            (new RequiredSecretsValidator)->ensureAllPresent([
                'APP_KEY' => config('app.key'),
                'DB_PASSWORD' => config("database.connections.{$connection}.password"),
                'AWS_ACCESS_KEY_ID' => config('filesystems.disks.s3.key'),
                'AWS_SECRET_ACCESS_KEY' => config('filesystems.disks.s3.secret'),
                'AWS_BUCKET' => config('filesystems.disks.s3.bucket'),
            ]);
        }

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

        $this->commands([
            ScanForSecretsCommand::class,
        ]);
    }
}
