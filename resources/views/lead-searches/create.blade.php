<x-layouts.app title="Run AI Lead Search | Sales Tracker" heading="Run AI Lead Search" eyebrow="Queue a lead discovery prompt">
    <section class="panel mb-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-white">AI prompts</h3>
                <p class="mt-1 text-sm text-slate-400">Pick a saved prompt, or write custom criteria below.</p>
            </div>
            @can(\App\Support\Permissions::LEAD_SEARCH_PRESETS_VIEW)
                <a class="btn-secondary" href="{{ route('lead-search-presets.index') }}">Manage prompts</a>
            @endcan
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse ($presets as $preset)
                <a
                    href="{{ route('lead-searches.create', ['preset' => $preset->slug]) }}"
                    @class([
                        'rounded-2xl border px-4 py-3 text-sm transition',
                        'border-sky-500/40 bg-sky-500/10 text-sky-100' => request('preset') === $preset->slug,
                        'border-slate-800 bg-slate-950/70 text-slate-200 hover:border-slate-700' => request('preset') !== $preset->slug,
                    ])
                >
                    {{ $preset->name }}
                </a>
            @empty
                <p class="md:col-span-2 text-sm text-slate-500">No active prompts yet.
                    @can(\App\Support\Permissions::LEAD_SEARCH_PRESETS_CREATE)
                        <a class="link-action" href="{{ route('lead-search-presets.create') }}">Create one</a>
                    @endcan
                </p>
            @endforelse
        </div>
    </section>

    <section class="panel">
        <form method="post" action="{{ route('lead-searches.store') }}" class="space-y-6">
            @csrf
            <div>
                <label class="label" for="criteria">Search criteria</label>
                <textarea class="input min-h-64" id="criteria" name="criteria" required>{{ old('criteria', $presetCriteria ?? '') }}</textarea>
                <p class="mt-2 text-sm text-slate-500">The queued job calls OpenRouter and creates contacts with `source = ai_search`. Keep queue worker running.</p>
            </div>
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Queue search</button>
                <a class="btn-secondary" href="{{ route('lead-searches.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
