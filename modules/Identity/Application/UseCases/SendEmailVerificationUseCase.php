<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Identity\Application\Commands\SendEmailVerificationCommand;
use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\EmailNotificationSender;

final readonly class SendEmailVerificationUseCase
{
    public function __construct(
        private UserRepository $users,
        private EmailVerificationTokenRepository $tokens,
        private AuditLogger $auditLogger,
        private EmailNotificationSender $mailer,
    ) {}

    public function execute(SendEmailVerificationCommand $command): void
    {
        $email = Email::fromString($command->email);
        $user = $this->users->findByEmail($email);

        if ($user === null || $user->emailVerifiedAt() !== null) {
            return;
        }

        $plainToken = Str::random(64);

        $this->tokens->save(EmailVerificationToken::issue(
            email: $email,
            tokenHash: hash('sha256', $plainToken),
        ));

        $this->mailer->send(
            $user->id(),
            'Verifica tu correo electrónico',
            "Gracias por registrarte en EDUDRIVE.\n\n".
            "Tu código de verificación es: {$plainToken}\n\n".
            'Este código expira en 60 minutos.',
        );

        $this->auditLogger->log(new AuditEntry(
            action: 'auth.email_verification_requested',
            userId: $user->id(),
            entity: 'User',
            entityId: $user->id(),
        ));
    }
}
