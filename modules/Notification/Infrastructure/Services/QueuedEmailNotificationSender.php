<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Services;

use Modules\Notification\Application\Services\EmailNotificationSender;
use Modules\Notification\Infrastructure\Jobs\SendEmailNotificationJob;

final readonly class QueuedEmailNotificationSender implements EmailNotificationSender
{
    public function send(string $userId, string $subject, string $body): void
    {
        SendEmailNotificationJob::dispatch($userId, $subject, $body);
    }
}
