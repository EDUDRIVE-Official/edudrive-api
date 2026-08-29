<?php

declare(strict_types=1);

namespace Modules\Backup\Presentation\Console;

use DateTimeImmutable;
use Illuminate\Console\Command as ConsoleCommand;
use Modules\Backup\Application\Services\DatabaseDumper;
use Modules\FileStorage\Application\Contracts\FileStorage;
use Throwable;

final class BackupDatabaseCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * @var string
     */
    protected $description = 'Genera un respaldo de la base de datos y lo sube al almacenamiento configurado.';

    public function handle(DatabaseDumper $dumper, FileStorage $fileStorage): int
    {
        $localPath = tempnam(sys_get_temp_dir(), 'backup_');
        if ($localPath === false) {
            $this->error('No se pudo crear un archivo temporal para el respaldo.');

            return self::FAILURE;
        }

        $storagePath = sprintf('backups/postgres/%s.dump', (new DateTimeImmutable('now'))->format('Y-m-d_His'));

        try {
            $dumper->dump($localPath);
            $fileStorage->store($storagePath, $localPath);
        } catch (Throwable $e) {
            $this->error("No se pudo generar el respaldo: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            if (file_exists($localPath)) {
                unlink($localPath);
            }
        }

        $this->info("Respaldo generado y almacenado en \"{$storagePath}\".");

        return self::SUCCESS;
    }
}
