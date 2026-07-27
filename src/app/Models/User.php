<?php

declare(strict_types=1);

namespace App\Models;

use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Adaptador de compatibilidad para Laravel.
 *
 * Toda la lógica y configuración del modelo de usuario reside en:
 *
 * Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel
 */
class User extends UserModel {}
