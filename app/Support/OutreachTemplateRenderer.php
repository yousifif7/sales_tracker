<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\EmailTemplate;

class OutreachTemplateRenderer
{
    /**
     * @return array{subject: string, body: string}
     */
    public function render(string $templateKey, Contact $contact): array
    {
        $template = EmailTemplate::query()
            ->active()
            ->where(fn ($query) => $query->where('slug', $templateKey)->orWhere('id', $templateKey))
            ->first();

        if (! $template) {
            return [
                'subject' => '',
                'body' => '',
            ];
        }

        $replacements = [
            '{{company}}' => e($contact->company ?: 'your company'),
            '{{first_name}}' => e(str($contact->name)->before(' ')->toString() ?: $contact->name),
            '{{name}}' => e($contact->name),
        ];

        // Subject stays plain text; body may contain HTML so replace tokens carefully.
        $plainReplacements = [
            '{{company}}' => $contact->company ?: 'your company',
            '{{first_name}}' => str($contact->name)->before(' ')->toString() ?: $contact->name,
            '{{name}}' => $contact->name,
        ];

        return [
            'subject' => str_replace(array_keys($plainReplacements), array_values($plainReplacements), $template->subject),
            'body' => str_replace(array_keys($replacements), array_values($replacements), $template->body),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return EmailTemplate::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }
}
