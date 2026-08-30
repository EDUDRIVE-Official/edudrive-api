<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Repositories;

use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

interface UserConsentRepository
{
    public function save(UserConsent $consent): void;

    /** @return list<UserConsent> */
    public function findByUserId(string $userId): array;

    public function findLatestActiveByUserAndPolicy(string $userId, PolicyKey $policyKey): ?UserConsent;
}
