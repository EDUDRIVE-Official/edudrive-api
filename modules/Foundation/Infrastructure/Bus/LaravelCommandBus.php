<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Foundation\Application\Commands\Command;

final readonly class LaravelCommandBus implements CommandBus
{
    public function __construct(
        private Container $container,
        private MessageHandlerRegistry $registry,
    ) {}

    public function dispatch(Command $command): mixed
    {
        $handlerClass = $this->registry->handlerFor(
            $command::class,
        );

        $handler = $this->container->make($handlerClass);

        return $handler->handle($command);
    }
}
