<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Console;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Str;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class BootstrapSuperAdminCommand extends ConsoleCommand
{
    protected $signature = 'identity:bootstrap-super-admin
        {email : Correo de la primera cuenta administradora}
        {--name=Administración EduDrive : Nombre visible de la cuenta}';

    protected $description = 'Crea o activa el primer superadministrador y emite un enlace de contraseña de un solo uso.';

    public function handle(
        UserRepository $users,
        PasswordHasher $passwordHasher,
        PasswordResetTokenRepository $tokens,
        CommandBus $commandBus,
    ): int {
        $email = Email::fromString((string) $this->argument('email'));
        $user = $users->findByEmail($email);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($user === null) {
            $user = User::register(
                id: (string) Str::uuid(),
                name: (string) $this->option('name'),
                email: $email,
                passwordHash: $passwordHasher->hash(Str::random(64)),
                registeredAt: $now,
            );
        }

        $user->activate($now);
        $users->save($user);

        $commandBus->dispatch(new AssignRoleCommand(
            userId: $user->id(),
            role: Role::SuperAdmin->value,
            organizationId: null,
            actorId: $user->id(),
        ));

        $plainToken = Str::random(64);
        $tokens->save(PasswordResetToken::issue(
            email: $email,
            tokenHash: hash('sha256', $plainToken),
            createdAt: $now,
        ));

        $this->info('Superadministrador preparado correctamente.');
        $this->line(route('password.reset', [
            'token' => $plainToken,
            'email' => $email->value(),
        ]));

        return self::SUCCESS;
    }
}
