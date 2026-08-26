<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;

final class CreateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'string', 'uuid'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['integer', 'min:1'],
            'passing_score' => ['integer', 'min:1', 'max:100'],
            'shuffle_questions' => ['boolean'],
            'feedback_mode' => ['string', new Enum(ExamFeedbackMode::class)],
            'kind' => ['sometimes', 'string', new Enum(ExamKind::class)],
            'license_category' => [
                Rule::requiredIf(fn (): bool => $this->input('kind', 'standard') === ExamKind::Theory->value),
                'nullable',
                'string',
                'regex:/\S/',
                'max:50',
            ],
            'allow_partial_credit' => ['boolean'],
            'apply_penalties' => ['boolean'],
            'questions' => ['array'],
            'questions.*.question_id' => ['required', 'string', 'uuid'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
        ];
    }
}
