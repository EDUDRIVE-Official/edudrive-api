<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Console;

use Illuminate\Console\Command as ConsoleCommand;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Foundation\Application\Bus\CommandBus;

final class AssignRoleConsoleCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'authorization:assign-role {userId} {role} {--organization=}';

    /**
     * @var string
     */
    protected $description = 'Asigna un rol de autorización a un usuario (uso principal: bootstrap del primer superadministrador).';

    public function handle(CommandBus $commandBus): int
    {
        $commandBus->dispatch(
            new AssignRoleCommand(
                userId: (string) $this->argument('userId'),
                role: (string) $this->argument('role'),
                organizationId: $this->option('organization'),
            ),
        );

        $this->info('Rol asignado correctamente.');

        return self::SUCCESS;
    }
}
