<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Modules\Organization\Application\Commands\UpdateOrganizationCommand;
use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

final readonly class UpdateOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(UpdateOrganizationCommand $command): OrganizationListItemResponse
    {
        $organization = $this->organizations->findById(OrganizationId::fromString($command->organizationId));

        if ($organization === null) {
            throw OrganizationNotFound::withId($command->organizationId);
        }

        $organization->rename(OrganizationName::fromString($command->name));

        $this->organizations->save($organization);

        return OrganizationListItemResponse::fromOrganization($organization);
    }
}
