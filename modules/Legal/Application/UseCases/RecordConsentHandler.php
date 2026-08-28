<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Exceptions\PolicyNotFound;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final readonly class RecordConsentHandler
{
    public function __construct(
        private ConsentPolicyRepository $policies,
        private UserConsentRepository $consents,
    ) {}

    public function handle(RecordConsentCommand $command): ConsentResponse
    {
        $key = PolicyKey::fromString($command->policyKey);
        $policy = $this->policies->findCurrentByKey($key);

        if ($policy === null) {
            throw PolicyNotFound::withKey($command->policyKey);
        }

        $consent = UserConsent::accept(
            id: (string) Str::uuid(),
            userId: $command->userId,
            policyKey: $key,
            policyVersion: $policy->version(),
        );

        $this->consents->save($consent);

        return ConsentResponse::fromUserConsent($consent);
    }
}
