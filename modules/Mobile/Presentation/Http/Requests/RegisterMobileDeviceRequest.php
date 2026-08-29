<?php

declare(strict_types=1);

namespace Modules\Mobile\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:150'],
            'platform' => ['required', 'string'],
            'push_token' => ['nullable', 'string', 'max:255'],
            'app_version' => ['required', 'string', 'max:20'],
        ];
    }
}
