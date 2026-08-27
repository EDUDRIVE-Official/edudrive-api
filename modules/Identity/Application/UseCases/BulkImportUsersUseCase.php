<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\Commands\BulkImportUsersCommand;
use Modules\Identity\Application\DTO\RegisterUserCommand;
use Modules\Identity\Application\Exceptions\EmailAlreadyExists;
use Modules\Identity\Application\Responses\BulkImportUsersResponse;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Domain\Repositories\UserRepository;
use Throwable;

final readonly class BulkImportUsersUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private UuidGenerator $uuidGenerator,
        private CommandBus $commandBus,
    ) {}

    public function execute(BulkImportUsersCommand $command): BulkImportUsersResponse
    {
        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($command->rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $results[] = $this->importRow($rowNumber, $row);
                $created++;
            } catch (EmailAlreadyExists) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'EMAIL_ALREADY_EXISTS'];
            } catch (Throwable) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'IMPORT_ROW_INVALID'];
            }
        }

        return new BulkImportUsersResponse(
            total: count($command->rows),
            created: $created,
            failed: $failed,
            results: $results,
        );
    }

    /**
     * @param  array{name: string, email: string, password: string, role: string}  $row
     * @return array{row: int, created: bool, user_id: string, email: string}
     */
    private function importRow(int $rowNumber, array $row): array
    {
        $name = trim($row['name']);
        $email = trim($row['email']);
        $password = $row['password'];
        $roleValue = trim($row['role']);

        if ($name === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Fila incompleta: se requieren name, email y password.');
        }

        $role = Role::from($roleValue === '' ? Role::Student->value : $roleValue);

        return DB::transaction(function () use ($name, $email, $password, $role, $rowNumber): array {
            $registered = (new RegisterUserUseCase($this->users, $this->passwordHasher, $this->uuidGenerator))
                ->execute(new RegisterUserCommand(name: $name, email: $email, password: $password));

            $this->commandBus->dispatch(new AssignRoleCommand(
                userId: $registered->id,
                role: $role->value,
                organizationId: null,
            ));

            return [
                'row' => $rowNumber,
                'created' => true,
                'user_id' => $registered->id,
                'email' => $registered->email,
            ];
        });
    }
}
