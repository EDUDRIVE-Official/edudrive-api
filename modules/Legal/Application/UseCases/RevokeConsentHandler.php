<?php

declare(strict_types=1);

namespace Modules\Legal\Application\UseCases;

use DateTimeImmutable;
use Modules\Legal\Application\Commands\RevokeConsentCommand;
use Modules\Legal\Application\Exceptions\ConsentNotFound;
use Modules\Legal\Application\Responses\ConsentResponse;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

final readonly class RevokeConsentHandler
{
    public function __construct(
        private UserConsentRepository $consents,
    ) {}

    public function handle(RevokeConsentCommand $command): ConsentResponse
    {
        $consent = $this->consents->findLatestActiveByUserAndPolicy(
            $command->userId,
            PolicyKey::fromString($command->policyKey),
        );

        if ($consent === null) {
            throw ConsentNotFound::create();
        }

        $consent->revoke(new DateTimeImmutable);
        $this->consents->save($consent);

        return ConsentResponse::fromUserConsent($consent);
    }
}
