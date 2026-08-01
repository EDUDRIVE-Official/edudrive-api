<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Repositories\OrganizationRepository;

final readonly class ListOrganizationsHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    /**
     * @return list<OrganizationListItemResponse>
     */
    public function handle(ListOrganizationsQuery $query): array
    {
        return array_map(
            static fn (Organization $organization): OrganizationListItemResponse => OrganizationListItemResponse::fromOrganization($organization),
            $this->organizations->all(),
        );
    }
}
