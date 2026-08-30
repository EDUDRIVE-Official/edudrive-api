<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Identity\Application\Commands\SendEmailVerificationCommand;
use Modules\Identity\Application\DTO\RegisterUserCommand;
use Modules\Identity\Application\DTO\RegisterUserResponse;
use Modules\Identity\Application\Exceptions\EmailAlreadyExists;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private UuidGenerator $uuidGenerator,
        private SendEmailVerificationUseCase $emailVerification,
    ) {}

    public function execute(RegisterUserCommand $command): RegisterUserResponse
    {
        $email = Email::fromString($command->email);

        if ($this->users->existsByEmail($email)) {
            throw EmailAlreadyExists::withEmail($email->value());
        }

        $user = User::register(
            id: $this->uuidGenerator->generate(),
            name: $command->name,
            email: $email,
            passwordHash: $this->passwordHasher->hash($command->password),
            dateOfBirth: $command->dateOfBirth === null ? null : new DateTimeImmutable($command->dateOfBirth),
        );

        $this->users->save($user);

        $this->emailVerification->execute(new SendEmailVerificationCommand(email: $user->email()->value()));

        return new RegisterUserResponse(
            id: $user->id(),
            name: $user->name(),
            email: $user->email()->value(),
            status: $user->status()->value,
        );
    }
}
