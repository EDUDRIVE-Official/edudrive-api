<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'earning_rule' => ['required', 'string'],
        ];
    }
}
