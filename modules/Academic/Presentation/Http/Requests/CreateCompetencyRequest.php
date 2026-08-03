<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\CompetencyCategory;
use Modules\Academic\Domain\Enums\MasteryLevel;

final class CreateCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', new Enum(CompetencyCategory::class)],
            'mastery_level' => ['required', 'string', new Enum(MasteryLevel::class)],
        ];
    }
}
