<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use Modules\Legal\Application\Queries\ListPoliciesQuery;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;

final readonly class ListPoliciesHandler
{
    public function __construct(
        private ConsentPolicyRepository $policies,
    ) {}

    /** @return list<PolicyResponse> */
    public function handle(ListPoliciesQuery $query): array
    {
        return array_map(
            static fn (ConsentPolicy $policy): PolicyResponse => PolicyResponse::fromConsentPolicy($policy),
            $this->policies->allCurrent(),
        );
    }
}
