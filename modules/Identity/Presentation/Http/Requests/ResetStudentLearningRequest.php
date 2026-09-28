<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResetStudentLearningRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'confirmation' => ['required', 'in:REINICIAR'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Indicá el motivo del reinicio.',
            'reason.min' => 'El motivo debe tener al menos 10 caracteres.',
            'confirmation.in' => 'Escribí REINICIAR para confirmar la acción.',
        ];
    }
}
