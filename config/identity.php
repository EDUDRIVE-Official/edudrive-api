<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Periodo de retencion por inactividad
    |--------------------------------------------------------------------------
    |
    | Numero de anos sin inicio de sesion (o desde el registro, si nunca
    | inicio sesion) antes de que una cuenta sea candidata a eliminacion
    | fisica automatica por el comando identity:purge-inactive-accounts.
    |
    */

    'retention_inactivity_years' => env('IDENTITY_RETENTION_INACTIVITY_YEARS', 3),

];
