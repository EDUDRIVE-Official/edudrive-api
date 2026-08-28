<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Legal\Application\Commands\PublishPolicyVersionCommand;
use Modules\Legal\Application\Responses\PolicyResponse;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final readonly class PublishPolicyVersionHandler
{
    public function __construct(
        private ConsentPolicyRepository $policies,
    ) {}

    public function handle(PublishPolicyVersionCommand $command): PolicyResponse
    {
        $key = PolicyKey::fromString($command->key);
        $current = $this->policies->findCurrentByKey($key);
        $nextVersion = $current === null ? 1 : $current->version() + 1;

        $policy = ConsentPolicy::publish(
            id: (string) Str::uuid(),
            key: $key,
            version: $nextVersion,
            effectiveAt: $command->effectiveAt === null ? null : new DateTimeImmutable($command->effectiveAt),
        );

        $this->policies->save($policy);

        return PolicyResponse::fromConsentPolicy($policy);
    }
}
