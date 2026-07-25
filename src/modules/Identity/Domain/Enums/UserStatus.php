<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
