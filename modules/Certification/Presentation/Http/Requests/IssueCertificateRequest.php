<?php

declare(strict_types=1);

namespace Modules\Certification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IssueCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'course_id' => ['required', 'string', 'uuid', 'exists:academic_courses,id'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
