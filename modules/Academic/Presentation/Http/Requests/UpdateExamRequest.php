<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;

final class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['integer', 'min:1'],
            'passing_score' => ['integer', 'min:1', 'max:100'],
            'shuffle_questions' => ['boolean'],
            'feedback_mode' => ['string', new Enum(ExamFeedbackMode::class)],
            'questions' => ['array'],
            'questions.*.question_id' => ['required', 'string', 'uuid'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
        ];
    }
}
