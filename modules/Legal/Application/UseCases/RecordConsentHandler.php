<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Legal\Application\Commands\RecordConsentCommand;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Exceptions\GuardianDeclarationRequired;
use Modules\Legal\Domain\Exceptions\PolicyNotFound;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final readonly class RecordConsentHandler
{
    public function __construct(
        private ConsentPolicyRepository $policies,
        private UserConsentRepository $consents,
        private UserRepository $users,
    ) {}

    public function handle(RecordConsentCommand $command): ConsentResponse
    {
        $key = PolicyKey::fromString($command->policyKey);
        $policy = $this->policies->findCurrentByKey($key);

        if ($policy === null) {
            throw PolicyNotFound::withKey($command->policyKey);
        }

        $user = $this->users->findById($command->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $guardianDeclaration = $command->guardianDeclaration !== null && trim($command->guardianDeclaration) !== ''
            ? trim($command->guardianDeclaration)
            : null;

        if ($user->isMinor() && $guardianDeclaration === null) {
            throw GuardianDeclarationRequired::create();
        }

        $consent = UserConsent::accept(
            id: (string) Str::uuid(),
            userId: $command->userId,
            policyKey: $key,
            policyVersion: $policy->version(),
            guardianDeclaration: $user->isMinor() ? $guardianDeclaration : null,
        );

        $this->consents->save($consent);

        return ConsentResponse::fromUserConsent($consent);
    }
}
