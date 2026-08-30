<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Identity\Application\Exceptions\GuardianRelationshipNotFound;
use Modules\Identity\Application\Queries\GetLinkedMinorProgressQuery;
use Modules\Identity\Application\Responses\MyStudentProfileResponse;
use Modules\Identity\Application\Services\StudentProfileComposer;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;

final readonly class GetLinkedMinorProgressHandler
{
    public function __construct(
        private GuardianRelationshipRepository $relationships,
        private StudentProfileComposer $composer,
    ) {}

    public function handle(GetLinkedMinorProgressQuery $query): MyStudentProfileResponse
    {
        $relationship = $this->relationships->findActiveByGuardianAndMinor(
            $query->guardianUserId,
            $query->minorUserId,
        );

        if ($relationship === null) {
            throw new GuardianRelationshipNotFound;
        }

        return $this->composer->compose($query->minorUserId);
    }
}
