<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SuspendApiConsumerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
