<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterApiConsumerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
