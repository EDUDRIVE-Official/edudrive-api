<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GrantBadgeRequest extends FormRequest
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
            'evidence' => ['required', 'string'],
        ];
    }
}
