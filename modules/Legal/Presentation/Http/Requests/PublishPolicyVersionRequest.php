<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublishPolicyVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100'],
            'effective_at' => ['nullable', 'date'],
        ];
    }
}
