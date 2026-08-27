<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Application\Queries\GetOrganizationQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class GetOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(GetOrganizationQuery $query): OrganizationListItemResponse
    {
        $organization = $this->organizations->findById(OrganizationId::fromString($query->organizationId));

        if ($organization === null) {
            throw OrganizationNotFound::withId($query->organizationId);
        }

        return OrganizationListItemResponse::fromOrganization($organization);
    }
}
