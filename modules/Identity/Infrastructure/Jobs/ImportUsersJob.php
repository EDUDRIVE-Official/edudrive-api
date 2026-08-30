<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Jobs;

use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Identity\Application\DTO\RegisterUserCommand;
use Modules\Identity\Application\Exceptions\EmailAlreadyExists;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Application\UseCases\RegisterUserUseCase;
use Modules\Identity\Application\UseCases\SendEmailVerificationUseCase;
use Modules\Identity\Domain\Repositories\UserRepository;
use Throwable;

final class ImportUsersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public readonly ?string $correlationId;

    /** @param list<array{name: string, email: string, password: string, role: string}> $rows */
    public function __construct(
        public readonly string $asyncJobId,
        public readonly array $rows,
        public readonly string $actorId,
    ) {
        $this->correlationId = Context::get('correlation_id');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        AsyncJobRepository $jobs,
        UserRepository $users,
        PasswordHasher $passwordHasher,
        UuidGenerator $uuidGenerator,
        SendEmailVerificationUseCase $emailVerification,
        CommandBus $commandBus,
    ): void {
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->start(new DateTimeImmutable('now'));
        $jobs->save($job);

        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($this->rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $results[] = $this->importRow($users, $passwordHasher, $uuidGenerator, $emailVerification, $commandBus, $rowNumber, $row);
                $created++;
            } catch (EmailAlreadyExists) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'EMAIL_ALREADY_EXISTS'];
            } catch (Throwable) {
                $failed++;
                $results[] = ['row' => $rowNumber, 'created' => false, 'error_code' => 'IMPORT_ROW_INVALID'];
            }
        }

        $job->complete([
            'total' => count($this->rows),
            'created' => $created,
            'failed' => $failed,
            'results' => $results,
        ], new DateTimeImmutable('now'));
        $jobs->save($job);
    }

    /**
     * @param  array{name: string, email: string, password: string, role: string}  $row
     * @return array{row: int, created: bool, user_id: string, email: string}
     */
    private function importRow(
        UserRepository $users,
        PasswordHasher $passwordHasher,
        UuidGenerator $uuidGenerator,
        SendEmailVerificationUseCase $emailVerification,
        CommandBus $commandBus,
        int $rowNumber,
        array $row,
    ): array {
        $name = trim($row['name']);
        $email = trim($row['email']);
        $password = $row['password'];
        $roleValue = trim($row['role']);

        if ($name === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Fila incompleta: se requieren name, email y password.');
        }

        $role = Role::from($roleValue === '' ? Role::Student->value : $roleValue);

        return DB::transaction(function () use ($users, $passwordHasher, $uuidGenerator, $emailVerification, $commandBus, $name, $email, $password, $role, $rowNumber): array {
            $registered = (new RegisterUserUseCase($users, $passwordHasher, $uuidGenerator, $emailVerification))
                ->execute(new RegisterUserCommand(name: $name, email: $email, password: $password));

            $commandBus->dispatch(new AssignRoleCommand(
                userId: $registered->id,
                role: $role->value,
                organizationId: null,
                actorId: $this->actorId,
            ));

            return [
                'row' => $rowNumber,
                'created' => true,
                'user_id' => $registered->id,
                'email' => $registered->email,
            ];
        });
    }

    public function failed(?Throwable $exception): void
    {
        $jobs = app(AsyncJobRepository::class);
        $job = $jobs->findById(AsyncJobId::fromString($this->asyncJobId));
        if ($job === null) {
            return;
        }

        $job->fail($exception?->getMessage() ?? 'Error desconocido al importar usuarios.', new DateTimeImmutable('now'));
        $jobs->save($job);

        Log::warning('Fallo la importacion asincrona de usuarios.', [
            'async_job_id' => $this->asyncJobId,
            'correlation_id' => $this->correlationId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
