<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Console;

use DateTimeImmutable;
use Illuminate\Console\Command as ConsoleCommand;
use Modules\Identity\Application\Commands\DeleteAccountCommand;
use Modules\Identity\Application\UseCases\DeleteAccountUseCase;
use Modules\Identity\Domain\Repositories\UserRepository;

final class PurgeInactiveAccountsCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'identity:purge-inactive-accounts';

    /**
     * @var string
     */
    protected $description = 'Elimina fisicamente las cuentas inactivas mas alla del periodo de retencion configurado (politica de retencion de datos personales).';

    public function handle(UserRepository $users, DeleteAccountUseCase $deleteAccount): int
    {
        $inactivityYears = (int) config('identity.retention_inactivity_years');
        $threshold = new DateTimeImmutable(sprintf('-%d years', $inactivityYears));

        $inactiveUsers = $users->findInactiveBefore($threshold);

        foreach ($inactiveUsers as $user) {
            $deleteAccount->execute(new DeleteAccountCommand(userId: $user->id()));
        }

        $this->info(sprintf('Cuentas eliminadas por inactividad: %d.', count($inactiveUsers)));

        return self::SUCCESS;
    }
}
