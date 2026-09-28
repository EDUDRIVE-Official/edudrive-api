<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Identity\Application\Commands\UpdateUserDateOfBirthCommand;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class UpdateUserDateOfBirthHandler
{
    public function __construct(private UserRepository $users) {}

    public function handle(UpdateUserDateOfBirthCommand $command): void
    {
        $user = $this->users->findById($command->userId);
        if ($user === null) {
            throw new UserNotFound;
        }

        $user->changeDateOfBirth(
            $command->dateOfBirth === null ? null : new DateTimeImmutable($command->dateOfBirth),
            new DateTimeImmutable,
        );
        $this->users->save($user);
    }
}
