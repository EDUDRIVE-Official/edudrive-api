<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Services;

use Modules\Mobile\Domain\ValueObjects\MobilePushMessage;

interface MobilePushSender
{
    public function send(MobilePushMessage $message): void;
}
