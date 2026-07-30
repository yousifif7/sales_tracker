<?php

namespace Database\Seeders;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class StrategyTargetsSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            [
                'name' => 'David Law',
                'company' => 'JD Security Solutions',
                'email' => null,
                'status' => ContactStatus::New,
                'notes' => "Queued from FieldLine strategy. Bangor, North Wales. Connect note + email drafted. Next target.",
                'tags' => ['fieldline', 'queued', 'wales'],
            ],
            [
                'name' => 'Russ Webster',
                'company' => 'Glentworth Security',
                'email' => 'russwebster@glentworthsecurity.co.uk',
                'status' => ContactStatus::New,
                'notes' => "Priority from SPL Connect kit. Optional CC: info@glentworthsecurity.co.uk. Use Glentworth template. Demo: splconnect.yousiffarra.com",
                'tags' => ['spl-connect', 'priority', 'glentworth'],
            ],
            [
                'name' => 'Sandeep Singh',
                'company' => 'SK Security Services',
                'email' => null,
                'status' => ContactStatus::Contacted,
                'notes' => 'Watford, ~11-20 employees. LinkedIn connect + message already sent (FieldLine strategy log).',
                'tags' => ['fieldline', 'contacted', 'watford'],
            ],
            [
                'name' => 'Muhmmad Ateeb Akhtar',
                'company' => 'Secure Security Services',
                'email' => null,
                'status' => ContactStatus::Contacted,
                'notes' => 'Leicester, ~21-50 employees. LinkedIn connect + message already sent (FieldLine strategy log).',
                'tags' => ['fieldline', 'contacted', 'leicester'],
            ],
            [
                'name' => 'Harry Hussain',
                'company' => 'Uniguard',
                'email' => null,
                'status' => ContactStatus::Lost,
                'notes' => 'DEPRIORITIZED: company already advertises real-time KPI monitoring / systems innovation.',
                'tags' => ['fieldline', 'deprioritized', 'birmingham'],
            ],
        ];

        foreach ($targets as $target) {
            $tags = $target['tags'];
            unset($target['tags']);

            $attributes = filled($target['email'])
                ? ['email' => $target['email']]
                : ['name' => $target['name'], 'company' => $target['company']];

            $contact = Contact::query()->updateOrCreate($attributes, [
                ...$target,
                'source' => ContactSource::Manual,
            ]);

            $tagIds = collect($tags)
                ->map(fn (string $tag) => Tag::query()->firstOrCreate(['name' => $tag])->id)
                ->all();

            $contact->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
