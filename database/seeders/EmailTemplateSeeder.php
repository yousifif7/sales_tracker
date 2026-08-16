<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\HtmlContent;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('outreach.templates', []) as $slug => $template) {
            EmailTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $template['label'],
                    'subject' => $template['subject'],
                    'body' => HtmlContent::plainToHtml((string) $template['body']),
                    'is_active' => (bool) ($template['active'] ?? true),
                ],
            );
        }
    }
}
