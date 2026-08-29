<?php

declare(strict_types=1);

use Modules\Backup\Infrastructure\Services\PgDumpDatabaseDumper;

it('arma la linea de comando de pg_dump con la conexion configurada', function (): void {
    config(['database.connections.pgsql' => [
        'host' => 'postgres',
        'port' => '5432',
        'database' => 'edudrive',
        'username' => 'edudrive',
        'password' => 'secret',
    ]]);

    $commandLine = (new PgDumpDatabaseDumper)->commandLine('/tmp/backup.dump');

    expect($commandLine)->toBe([
        'pg_dump',
        '-h', 'postgres',
        '-p', '5432',
        '-U', 'edudrive',
        '-d', 'edudrive',
        '-Fc',
        '-f', '/tmp/backup.dump',
    ]);
});
