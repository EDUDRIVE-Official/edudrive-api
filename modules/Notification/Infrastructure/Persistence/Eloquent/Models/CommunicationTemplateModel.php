<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunicationTemplateModel extends Model
{
    protected $table = 'communication_templates';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'registered_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }
}
