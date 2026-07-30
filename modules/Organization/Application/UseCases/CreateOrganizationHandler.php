<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

final readonly class CreateOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(
        CreateOrganizationCommand $command,
    ): CreateOrganizationResponse {
        $organization = Organization::create(
            id: OrganizationId::fromString((string) Str::uuid()),
            name: OrganizationName::fromString($command->name),
            type: OrganizationType::from($command->type),
        );

        $this->organizations->save($organization);

        return CreateOrganizationResponse::fromOrganization($organization);
    }
}
