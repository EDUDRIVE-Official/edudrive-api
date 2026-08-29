<?php

declare(strict_types=1);

namespace Modules\Mobile\Domain\ValueObjects;

final readonly class MobilePushMessage
{
    public function __construct(
        public string $userId,
        public string $title,
        public string $body,
    ) {}
}
