<x-layouts.app title="New AI Prompt | Sales Tracker" heading="New AI Prompt" eyebrow="Lead search preset">
    <section class="panel max-w-3xl">
        <form method="post" action="{{ route('lead-search-presets.store') }}" class="space-y-5">
            @csrf
            @include('lead-search-presets._form', ['preset' => $preset])
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Save prompt</button>
                <a class="btn-secondary" href="{{ route('lead-search-presets.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
