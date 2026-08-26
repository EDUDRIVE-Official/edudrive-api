<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecordExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'uuid'],
            'points' => ['required', 'integer', 'min:1'],
            'competency_id' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string'],
        ];
    }
}
