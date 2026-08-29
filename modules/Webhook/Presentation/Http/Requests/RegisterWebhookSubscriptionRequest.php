<?php

declare(strict_types=1);

namespace Modules\Webhook\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterWebhookSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
        ];
    }
}
