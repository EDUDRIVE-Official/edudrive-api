<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\CourseModality;

final class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
            ],
            'title' => [
                'required',
                'string',
                'max:180',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'objectives' => [
                'nullable',
                'string',
            ],
            'prerequisites' => [
                'nullable',
                'string',
            ],
            'modality' => [
                'nullable',
                'string',
                new Enum(CourseModality::class),
            ],
            'duration_hours' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del curso es obligatorio.',
            'code.regex' => 'El código solo puede contener letras, números y guiones intermedios.',
            'code.max' => 'El código no puede superar 50 caracteres.',
            'title.required' => 'El título del curso es obligatorio.',
            'title.max' => 'El título no puede superar 180 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'objectives.string' => 'Los objetivos deben ser texto.',
            'prerequisites.string' => 'Los requisitos deben ser texto.',
            'modality.enum' => 'La modalidad del curso no es válida.',
            'duration_hours.integer' => 'La duración debe ser un número entero de horas.',
            'duration_hours.min' => 'La duración debe ser de al menos 1 hora.',
        ];
    }
}
