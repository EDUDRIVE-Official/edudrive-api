<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Services;

use Modules\Backup\Application\Services\DatabaseDumper;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PgDumpDatabaseDumper implements DatabaseDumper
{
    public function dump(string $localPath): void
    {
        $process = new Process($this->commandLine($localPath), null, $this->environment());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException("pg_dump fallo: {$process->getErrorOutput()}");
        }
    }

    /** @return list<string> */
    public function commandLine(string $localPath): array
    {
        $connection = $this->connectionConfig();

        return [
            'pg_dump',
            '-h', (string) $connection['host'],
            '-p', (string) $connection['port'],
            '-U', (string) $connection['username'],
            '-d', (string) $connection['database'],
            '-Fc',
            '-f', $localPath,
        ];
    }

    /** @return array<string, string> */
    private function environment(): array
    {
        return ['PGPASSWORD' => (string) $this->connectionConfig()['password']];
    }

    /** @return array<string, mixed> */
    private function connectionConfig(): array
    {
        /** @var array<string, mixed> $connection */
        $connection = config('database.connections.pgsql');

        return $connection;
    }
}
