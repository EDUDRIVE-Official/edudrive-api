<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Legal\Application\Queries\GetOrganizationMinorsConsentsQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Application\Responses\OrganizationMinorConsentsResponse;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;

final readonly class GetOrganizationMinorsConsentsHandler
{
    public function __construct(
        private EnrollmentRepository $enrollments,
        private UserRepository $users,
        private UserConsentRepository $consents,
    ) {}

    /** @return list<OrganizationMinorConsentsResponse> */
    public function handle(GetOrganizationMinorsConsentsQuery $query): array
    {
        $userIds = array_unique(array_map(
            static fn (Enrollment $enrollment): string => $enrollment->userId(),
            $this->enrollments->all(organizationId: $query->organizationId),
        ));

        $responses = [];

        foreach ($userIds as $userId) {
            $user = $this->users->findById($userId);

            if ($user === null || ! $user->isMinor()) {
                continue;
            }

            $responses[] = new OrganizationMinorConsentsResponse(
                userId: $user->id(),
                name: $user->name(),
                consents: array_map(
                    static fn (UserConsent $consent): ConsentResponse => ConsentResponse::fromUserConsent($consent),
                    $this->consents->findByUserId($user->id()),
                ),
            );
        }

        return $responses;
    }
}
