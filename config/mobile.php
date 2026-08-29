<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint de notificaciones push
    |--------------------------------------------------------------------------
    |
    | Endpoint HTTP genérico compatible con la forma de la API HTTP legacy
    | de FCM al que se envían las notificaciones push móviles. Configurable
    | para poder apuntar a un proveedor real sin cambiar código.
    |
    */

    'push_endpoint' => env('MOBILE_PUSH_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),

    'push_server_key' => env('MOBILE_PUSH_SERVER_KEY'),

];
