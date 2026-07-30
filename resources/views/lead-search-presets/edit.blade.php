<x-layouts.app title="Edit AI Prompt | Sales Tracker" heading="Edit AI Prompt" eyebrow="{{ $preset->name }}">
    <section class="panel max-w-3xl">
        <form method="post" action="{{ route('lead-search-presets.update', $preset) }}" class="space-y-5">
            @csrf
            @method('put')
            @include('lead-search-presets._form', ['preset' => $preset])
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Update prompt</button>
                <a class="btn-secondary" href="{{ route('lead-search-presets.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
