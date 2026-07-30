<x-layouts.app title="New Interaction | Sales Tracker" heading="Log Interaction" eyebrow="Record outreach activity">
    <section class="panel">
        <form method="post" action="{{ route('interactions.store') }}" class="space-y-6">
            @csrf
            @include('interactions._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save interaction</button>
                <a class="btn-secondary" href="{{ route('interactions.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
