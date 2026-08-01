<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Organization\Application\Commands\AddCampusCommand;
use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Application\Responses\AddCampusResponse;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class AddCampusHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(
        AddCampusCommand $command,
    ): AddCampusResponse {
        $organizationId = OrganizationId::fromString($command->organizationId);
        $organization = $this->organizations->findById($organizationId);

        if ($organization === null) {
            throw OrganizationNotFound::withId($command->organizationId);
        }

        $campus = Campus::create(
            id: (string) Str::uuid(),
            name: $command->name,
        );

        $organization->addCampus($campus);

        $this->organizations->save($organization);

        return AddCampusResponse::fromCampus($campus);
    }
}
