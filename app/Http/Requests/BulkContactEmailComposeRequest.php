<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkContactEmailComposeRequest extends FormRequest
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
            'template' => ['nullable', 'string'],
        ];
    }
}
