<?php

declare(strict_types=1);

namespace Modules\FileStorage\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class FileStorageServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );
    }
}
