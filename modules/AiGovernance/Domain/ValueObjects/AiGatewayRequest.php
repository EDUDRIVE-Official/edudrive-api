<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\ValueObjects;

final readonly class AiGatewayRequest
{
    public function __construct(
        public string $aiSystemId,
        public ?string $requestedByUserId,
        public ?string $promptId,
        public string $input,
    ) {}
}
