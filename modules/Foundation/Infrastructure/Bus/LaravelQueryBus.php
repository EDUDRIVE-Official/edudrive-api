<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Bus;

use Illuminate\Contracts\Container\Container;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Application\Queries\Query;

final readonly class LaravelQueryBus implements QueryBus
{
    public function __construct(
        private Container $container,
        private MessageHandlerRegistry $registry,
    ) {}

    public function ask(Query $query): mixed
    {
        $handlerClass = $this->registry->handlerFor(
            $query::class,
        );

        $handler = $this->container->make($handlerClass);

        return $handler->handle($query);
    }
}
