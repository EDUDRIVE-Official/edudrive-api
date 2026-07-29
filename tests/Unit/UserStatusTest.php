<?php

declare(strict_types=1);

use Modules\Identity\Domain\Enums\UserStatus;

it('solo permite autenticación a usuarios activos', function (): void {
    expect(UserStatus::Active->canAuthenticate())
        ->toBeTrue()
        ->and(UserStatus::Pending->canAuthenticate())
        ->toBeFalse()
        ->and(UserStatus::Inactive->canAuthenticate())
        ->toBeFalse()
        ->and(UserStatus::Locked->canAuthenticate())
        ->toBeFalse();
});
