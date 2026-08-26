<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enums;

enum NotificationStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
}
