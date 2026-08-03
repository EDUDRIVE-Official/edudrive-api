<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReplaceProgramCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'course_ids' => ['required', 'array', 'distinct'],
            'course_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
