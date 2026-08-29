<?php

declare(strict_types=1);

use Modules\Backup\Infrastructure\Services\PgRestoreDatabaseRestorer;

it('arma la linea de comando de pg_restore con la conexion configurada', function (): void {
    config(['database.connections.pgsql' => [
        'host' => 'postgres',
        'port' => '5432',
        'database' => 'edudrive',
        'username' => 'edudrive',
        'password' => 'secret',
    ]]);

    $commandLine = (new PgRestoreDatabaseRestorer)->commandLine('/tmp/backup.dump');

    expect($commandLine)->toBe([
        'pg_restore',
        '-h', 'postgres',
        '-p', '5432',
        '-U', 'edudrive',
        '-d', 'edudrive',
        '--clean',
        '--if-exists',
        '/tmp/backup.dump',
    ]);
});
