<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListEnrollmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'string', 'uuid'],
            'user_id' => ['nullable', 'string', 'uuid'],
            'organization_id' => ['nullable', 'string', 'uuid'],
            'status' => ['nullable', Rule::in(['pending', 'active', 'completed', 'canceled'])],
            'source' => ['nullable', Rule::in(['individual', 'bulk', 'institutional'])],
        ];
    }
}
