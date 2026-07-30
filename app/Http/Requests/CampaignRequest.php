<?php

namespace App\Http\Requests;

use App\Enums\CampaignChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
