<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExternalCreateInstitutionalEnrollmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'uuid'],
            'organization_id' => ['required', 'uuid'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['string'],
            'status' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }
}
