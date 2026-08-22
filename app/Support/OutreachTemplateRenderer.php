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
        $raw = $this->rawTemplate($templateKey);

        if ($raw['subject'] === '' && $raw['body'] === '') {
            return $raw;
        }

        return $this->applyTokens($raw['subject'], $raw['body'], $contact);
    }

    /**
     * Load a template without personalizing tokens (for bulk compose).
     * Falls back to config('outreach.templates') when the DB row is missing/inactive
     * so sequence automation does not silently exit as missing_template.
     *
     * @return array{subject: string, body: string}
     */
    public function rawTemplate(string $templateKey): array
    {
        $template = EmailTemplate::query()
            ->active()
            ->where(fn ($query) => $query->where('slug', $templateKey)->orWhere('id', $templateKey))
            ->first();

        if ($template) {
            return [
                'subject' => (string) $template->subject,
                'body' => (string) $template->body,
            ];
        }

        $config = config('outreach.templates.'.$templateKey);

        if (! is_array($config)) {
            return [
                'subject' => '',
                'body' => '',
            ];
        }

        $body = (string) ($config['body'] ?? '');

        // Config bodies are plain text; DB templates are already HTML.
        if ($body !== '' && ! str_contains($body, '<')) {
            $body = HtmlContent::plainToHtml($body);
        }

        return [
            'subject' => (string) ($config['subject'] ?? ''),
            'body' => $body,
        ];
    }

    /**
     * Replace {{name}}, {{first_name}}, {{company}} in freeform subject/body.
     *
     * @return array{subject: string, body: string}
     */
    public function applyTokens(string $subject, string $bodyHtml, Contact $contact): array
    {
        $replacements = [
            '{{company}}' => e($contact->company ?: 'your company'),
            '{{first_name}}' => e(str($contact->name)->before(' ')->toString() ?: $contact->name),
            '{{name}}' => e($contact->name),
        ];

        // Subject stays plain text; body may contain HTML so escape replacements.
        $plainReplacements = [
            '{{company}}' => $contact->company ?: 'your company',
            '{{first_name}}' => str($contact->name)->before(' ')->toString() ?: $contact->name,
            '{{name}}' => $contact->name,
        ];

        return [
            'subject' => str_replace(array_keys($plainReplacements), array_values($plainReplacements), $subject),
            'body' => str_replace(array_keys($replacements), array_values($replacements), $bodyHtml),
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
