<?php

namespace App\Http\Requests;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', Rule::enum(ContactSource::class)],
            'status' => ['required', Rule::enum(ContactStatus::class)],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'website' => ['nullable', 'url', 'max:2048'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
        ];
    }
}
