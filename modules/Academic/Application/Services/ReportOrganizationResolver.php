<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class ReportOrganizationResolver
{
    public function __construct(private OrganizationRepository $organizations) {}

    /**
     * @param  list<string>  $organizationIds
     * @return list<Organization>
     */
    public function resolve(array $organizationIds): array
    {
        if ($organizationIds === []) {
            return $this->organizations->all();
        }

        return array_map(function (string $organizationId): Organization {
            $organization = $this->organizations->findById(OrganizationId::fromString($organizationId));

            if ($organization === null) {
                throw OrganizationNotFound::withId($organizationId);
            }

            return $organization;
        }, $organizationIds);
    }
}
