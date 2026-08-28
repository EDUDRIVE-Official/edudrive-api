<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\DeleteAccountCommand;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class DeleteAccountUseCase
{
    public function __construct(
        private UserRepository $users,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(DeleteAccountCommand $command): void
    {
        $user = $this->users->findById($command->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $this->auditLogger->log(
            new AuditEntry(
                action: 'identity.account_deleted',
                userId: $command->actorId,
                entity: 'User',
                entityId: $command->userId,
                metadata: [
                    'email' => $user->email()->value(),
                    // El actor casi siempre coincide con el usuario eliminado (autoservicio):
                    // se duplica aqui porque la columna user_id se desvincula (nullOnDelete)
                    // en cuanto la fila de este mismo usuario se borre a continuacion.
                    'actor_id' => $command->actorId,
                ],
            ),
        );

        $this->users->delete($command->userId);
    }
}
