<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Repositories;

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    public function findById(OrganizationId $id): ?Organization;

    /**
     * @return list<Organization>
     */
    public function all(): array;
}
