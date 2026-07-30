<?php

namespace Database\Seeders;

use App\Models\LeadSearchPreset;
use Illuminate\Database\Seeder;

class LeadSearchPresetSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 0;

        foreach (config('outreach.lead_search_presets', []) as $slug => $preset) {
            LeadSearchPreset::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $preset['label'],
                    'criteria' => $preset['criteria'],
                    'is_active' => true,
                    'sort_order' => $sort++,
                ],
            );
        }
    }
}
