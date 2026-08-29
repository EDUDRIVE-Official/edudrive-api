<?php

declare(strict_types=1);

namespace Modules\Backup\Presentation\Console;

use Illuminate\Console\Command as ConsoleCommand;
use Modules\Backup\Application\Services\DatabaseRestorer;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Throwable;

final class RestoreDatabaseCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'backup:restore {path : Ruta del respaldo en el almacenamiento, ej. backups/postgres/2026-08-29_120000.dump} {--force : Omite la confirmacion}';

    /**
     * @var string
     */
    protected $description = 'Restaura la base de datos desde un respaldo almacenado. Operacion destructiva: reemplaza el esquema actual.';

    public function handle(DatabaseRestorer $restorer, FileStorage $fileStorage): int
    {
        /** @var string $storagePath */
        $storagePath = $this->argument('path');

        if (! $this->option('force') && ! $this->confirm(
            "Esto reemplazara el esquema actual de la base de datos con el respaldo \"{$storagePath}\". ¿Continuar?",
        )) {
            $this->info('Restauracion cancelada.');

            return self::SUCCESS;
        }

        $localPath = tempnam(sys_get_temp_dir(), 'restore_');
        if ($localPath === false) {
            $this->error('No se pudo crear un archivo temporal para la restauracion.');

            return self::FAILURE;
        }

        try {
            $fileStorage->readToLocalFile($storagePath, $localPath);
            $restorer->restore($localPath);
        } catch (Throwable $e) {
            $this->error("No se pudo restaurar el respaldo: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            if (file_exists($localPath)) {
                unlink($localPath);
            }
        }

        $this->info("Base de datos restaurada desde \"{$storagePath}\".");

        return self::SUCCESS;
    }
}
