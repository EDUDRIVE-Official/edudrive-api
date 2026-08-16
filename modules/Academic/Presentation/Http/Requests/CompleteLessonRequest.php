<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'time_spent_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
