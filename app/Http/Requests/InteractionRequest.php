<?php

namespace App\Http\Requests;

use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Enums\ResponseOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InteractionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'channel' => ['required', Rule::enum(InteractionChannel::class)],
            'direction' => ['required', Rule::enum(InteractionDirection::class)],
            'content' => ['required', 'string'],
            'sent_at' => ['nullable', 'date'],
            'response.outcome' => ['nullable', Rule::enum(ResponseOutcome::class)],
            'response.sentiment_notes' => ['nullable', 'string'],
            'response.follow_up_date' => ['nullable', 'date'],
        ];
    }
}
