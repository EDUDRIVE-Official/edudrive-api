<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\ActivateUserCommand;
use Modules\Identity\Application\Responses\ActivateUserResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class ActivateUserUseCase
{
    public function __construct(
        private UserRepository $users,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(
        ActivateUserCommand $command,
    ): ActivateUserResponse {
        $user = $this->users->findById($command->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $user->activate(new DateTimeImmutable);

        $this->users->save($user);

        $this->auditLogger->log(new AuditEntry(
            action: 'identity.account_activated',
            userId: $command->actorId,
            entity: 'User',
            entityId: $user->id(),
        ));

        return new ActivateUserResponse(
            userId: $user->id(),
            status: $user->status()->value,
            message: 'Usuario activado correctamente.',
        );
    }
}
