<?php

declare(strict_types=1);

namespace Modules\Foundation\Application\Bus;

interface MessageHandlerRegistry
{
    /**
     * @param  class-string  $messageClass
     * @param  class-string  $handlerClass
     */
    public function register(
        string $messageClass,
        string $handlerClass,
    ): void;

    /**
     * @param  class-string  $messageClass
     * @return class-string
     */
    public function handlerFor(string $messageClass): string;
}
