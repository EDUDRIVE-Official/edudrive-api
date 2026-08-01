<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Aggregates;

use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

final class Organization
{
    /**
     * @param  list<Campus>  $campuses
     */
    private function __construct(
        private readonly OrganizationId $id,
        private OrganizationName $name,
        private readonly OrganizationType $type,
        private array $campuses = [],
    ) {
        // @phpstan-ignore function.alreadyNarrowedType, function.alreadyNarrowedType (runtime guard: the `list<Campus>` docblock is not enforced by PHP itself)
        assert(array_is_list($campuses));
    }

    public static function create(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationType $type,
    ): self {
        return new self(
            id: $id,
            name: $name,
            type: $type,
            campuses: [],
        );
    }

    /**
     * @param  list<Campus>  $campuses
     */
    public static function restore(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationType $type,
        array $campuses,
    ): self {
        return new self(
            id: $id,
            name: $name,
            type: $type,
            campuses: $campuses,
        );
    }

    public function addCampus(Campus $campus): void
    {
        $this->campuses[] = $campus;
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function name(): OrganizationName
    {
        return $this->name;
    }

    public function type(): OrganizationType
    {
        return $this->type;
    }

    /**
     * @return list<Campus>
     */
    public function campuses(): array
    {
        return $this->campuses;
    }
}
