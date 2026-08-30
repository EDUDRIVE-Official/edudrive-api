<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Queries\ListMyLinkedMinorsQuery;
use Modules\Identity\Application\Responses\LinkedMinorResponse;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class ListMyLinkedMinorsHandler
{
    public function __construct(
        private GuardianRelationshipRepository $relationships,
        private UserRepository $users,
    ) {}

    /** @return list<LinkedMinorResponse> */
    public function handle(ListMyLinkedMinorsQuery $query): array
    {
        return array_values(array_filter(array_map(
            function (GuardianRelationship $relationship): ?LinkedMinorResponse {
                $minor = $this->users->findById($relationship->minorUserId());

                if ($minor === null) {
                    return null;
                }

                return new LinkedMinorResponse(
                    userId: $minor->id(),
                    name: $minor->name(),
                );
            },
            $this->relationships->findActiveByGuardian($query->guardianUserId),
        )));
    }
}
