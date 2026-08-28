<?php

declare(strict_types=1);

namespace Modules\Legal\Domain\Repositories;

use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

interface ConsentPolicyRepository
{
    public function save(ConsentPolicy $policy): void;

    public function findCurrentByKey(PolicyKey $key): ?ConsentPolicy;

    /** @return list<ConsentPolicy> */
    public function allCurrent(): array;
}
