<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Services;

use Modules\AiGovernance\Domain\ValueObjects\AiGatewayRequest;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayResponse;

interface AiGatewayClient
{
    public function invoke(AiGatewayRequest $request): AiGatewayResponse;
}
