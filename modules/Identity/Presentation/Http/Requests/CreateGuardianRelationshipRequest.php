<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGuardianRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'guardian_user_id' => ['required', 'string', 'uuid'],
            'minor_user_id' => ['required', 'string', 'uuid'],
        ];
    }
}
