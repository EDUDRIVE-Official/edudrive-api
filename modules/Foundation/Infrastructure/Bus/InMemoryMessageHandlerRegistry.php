<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Bus;

use Modules\Foundation\Application\Bus\Exceptions\MessageHandlerNotFound;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class InMemoryMessageHandlerRegistry implements MessageHandlerRegistry
{
    /**
     * @var array<class-string, class-string>
     */
    private array $handlers = [];

    public function register(
        string $messageClass,
        string $handlerClass,
    ): void {
        $this->handlers[$messageClass] = $handlerClass;
    }

    public function handlerFor(string $messageClass): string
    {
        return $this->handlers[$messageClass]
            ?? throw MessageHandlerNotFound::forMessage($messageClass);
    }
}
