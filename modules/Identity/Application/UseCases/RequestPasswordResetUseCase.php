<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\RequestPasswordResetCommand;
use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\EmailNotificationSender;

final readonly class RequestPasswordResetUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private AuditLogger $auditLogger,
        private EmailNotificationSender $mailer,
    ) {}

    public function execute(RequestPasswordResetCommand $command): void
    {
        $email = Email::fromString($command->email);
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->auditLogger->log(new AuditEntry(
                action: 'auth.password_reset_requested',
                entity: 'User',
                metadata: ['email' => $command->email],
                outcome: 'failure',
            ));

            return;
        }

        $plainToken = Str::random(64);

        $this->tokens->save(PasswordResetToken::issue(
            email: $email,
            tokenHash: hash('sha256', $plainToken),
        ));

        $this->mailer->send(
            $user->id(),
            'Recuperación de contraseña',
            "Recibimos una solicitud para restablecer tu contraseña.\n\n".
            "Tu código de recuperación es: {$plainToken}\n\n".
            'Este código expira en 60 minutos. Si no solicitaste este cambio, ignora este mensaje.',
        );

        $this->auditLogger->log(new AuditEntry(
            action: 'auth.password_reset_requested',
            userId: $user->id(),
            entity: 'User',
            entityId: $user->id(),
        ));
    }
}
