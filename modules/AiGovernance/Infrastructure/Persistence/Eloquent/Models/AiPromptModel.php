<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $identifier
 * @property string $purpose
 * @property string|null $model_id
 * @property int $version
 * @property string|null $author_id
 * @property string $content
 * @property string $status
 */
final class AiPromptModel extends Model
{
    protected $table = 'ai_governance_prompts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
