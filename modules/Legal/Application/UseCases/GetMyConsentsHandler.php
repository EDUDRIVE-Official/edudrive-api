<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use Modules\Legal\Application\Queries\GetMyConsentsQuery;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;

final readonly class GetMyConsentsHandler
{
    public function __construct(
        private UserConsentRepository $consents,
    ) {}

    /** @return list<ConsentResponse> */
    public function handle(GetMyConsentsQuery $query): array
    {
        return array_map(
            static fn (UserConsent $consent): ConsentResponse => ConsentResponse::fromUserConsent($consent),
            $this->consents->findByUserId($query->userId),
        );
    }
}
