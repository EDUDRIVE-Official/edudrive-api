<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Retencion de trabajos asincronos
    |--------------------------------------------------------------------------
    |
    | Horas que se conserva un AsyncJob ya finalizado (Completed o Failed)
    | antes de que el comando async-processing:cleanup lo purgue. Los
    | archivos de exportacion asociados (result.storage_path) se borran del
    | almacenamiento antes de purgar el registro.
    |
    */

    'retention_hours' => (int) env('ASYNC_JOB_RETENTION_HOURS', 24),

];
