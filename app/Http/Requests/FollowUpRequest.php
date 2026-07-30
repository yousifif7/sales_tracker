<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpRequest extends FormRequest
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
            'due_date' => ['required', 'date'],
            'note' => ['required', 'string'],
            'completed' => ['nullable', 'boolean'],
        ];
    }
}
