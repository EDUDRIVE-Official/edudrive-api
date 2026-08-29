<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint del AI Gateway
    |--------------------------------------------------------------------------
    |
    | Endpoint HTTP generico y configurable al que el AI Gateway institucional
    | reenvia las solicitudes. No hay ninguna integracion real con un
    | proveedor de IA todavia; este valor es reemplazable cuando exista una.
    |
    */

    'gateway_endpoint' => env('AI_GATEWAY_ENDPOINT', 'http://localhost/ai-gateway'),

    'gateway_api_key' => env('AI_GATEWAY_API_KEY'),

];
