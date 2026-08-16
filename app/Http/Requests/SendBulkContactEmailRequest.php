<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendBulkContactEmailRequest extends FormRequest
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
            'contact_ids' => ['required', 'array', 'min:1', 'max:100'],
            'contact_ids.*' => ['integer', 'distinct', 'exists:contacts,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'template' => [
                'nullable',
                'string',
                Rule::exists('email_templates', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'enroll_in_sequence' => ['sometimes', 'boolean'],
        ];
    }
}
