<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Console;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Str;
use Modules\Academic\Infrastructure\Services\PilotVisualReviewers;
use Modules\Academic\Infrastructure\Services\PilotVisualReviews;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\Commands\RequestPasswordResetCommand;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\UseCases\RequestPasswordResetUseCase;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InvitePilotReviewerCommand extends ConsoleCommand
{
    protected $signature = 'pilot:invite-reviewer
        {email : Correo de la persona revisora}
        {--name= : Nombre completo}
        {--specialty= : education, road_safety o accessibility}
        {--organization= : Organización declarada}
        {--qualification= : Cualificación o experiencia}
        {--evidence= : Referencia verificable del respaldo}
        {--actor-email=admin@edudrive.test : Cuenta que registra la designación}';

    protected $description = 'Prepara una cuenta revisora del piloto y envía un enlace personal para crear su contraseña.';

    public function handle(
        UserRepository $users,
        PasswordHasher $passwordHasher,
        CommandBus $commandBus,
        PilotVisualReviewers $reviewers,
        RequestPasswordResetUseCase $requestPasswordReset,
    ): int {
        $values = [
            'name' => trim((string) $this->option('name')),
            'specialty' => trim((string) $this->option('specialty')),
            'organization' => trim((string) $this->option('organization')),
            'qualification' => trim((string) $this->option('qualification')),
            'evidence' => trim((string) $this->option('evidence')),
        ];

        foreach ($values as $key => $value) {
            if ($value === '') {
                $this->error("La opción --{$key} es obligatoria.");

                return self::FAILURE;
            }
        }
        if (! in_array($values['specialty'], PilotVisualReviews::SPECIALTIES, true)) {
            $this->error('La especialidad debe ser education, road_safety o accessibility.');

            return self::FAILURE;
        }

        $actor = $users->findByEmail(Email::fromString((string) $this->option('actor-email')));
        if ($actor === null) {
            $this->error('No existe la cuenta responsable de la designación.');

            return self::FAILURE;
        }

        $email = Email::fromString((string) $this->argument('email'));
        $user = $users->findByEmail($email);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($user === null) {
            $user = User::register(
                id: (string) Str::uuid(),
                name: $values['name'],
                email: $email,
                passwordHash: $passwordHasher->hash(Str::random(64)),
                registeredAt: $now,
            );
        } elseif ($user->name() !== $values['name']) {
            $user->rename($values['name'], $now);
        }

        $user->activate($now);
        $users->save($user);
        $commandBus->dispatch(new AssignRoleCommand(
            userId: $user->id(),
            role: Role::Teacher->value,
            organizationId: null,
            actorId: $actor->id(),
        ));

        $designation = $reviewers->activeForUser($user->id());
        $expected = [
            'specialty' => $values['specialty'],
            'organization' => $values['organization'],
            'qualification' => $values['qualification'],
            'evidence_reference' => $values['evidence'],
        ];
        $designationMatches = $designation !== null
            && collect($expected)->every(static fn (string $value, string $key): bool => ($designation[$key] ?? null) === $value);

        if (! $designationMatches) {
            $reviewers->save([
                'user_id' => $user->id(),
                ...$expected,
                'designated_on' => $now->format('Y-m-d'),
                'active' => true,
            ], $actor->id());
        }

        $requestPasswordReset->execute(new RequestPasswordResetCommand($email->value()));
        $this->info("Invitación preparada y encolada para {$email->value()}.");

        return self::SUCCESS;
    }
}
