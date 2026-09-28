<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Instrumentos del piloto
    |--------------------------------------------------------------------------
    |
    | Los instrumentos permanecen ocultos en producción hasta que la
    | organización decida abrir formalmente el piloto. Las rutas conservan
    | además sus controles de autenticación, permisos y designación revisora.
    |
    */
    'instruments_enabled' => (bool) env('PILOT_INSTRUMENTS_ENABLED', false),
];
