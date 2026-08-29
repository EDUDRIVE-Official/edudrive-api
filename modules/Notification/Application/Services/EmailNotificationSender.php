<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

interface EmailNotificationSender
{
    public function send(string $userId, string $subject, string $body): void;
}
