<x-layouts.app title="New Follow-up | Sales Tracker" heading="New Follow-up" eyebrow="Schedule reminder">
    <section class="panel">
        <form method="post" action="{{ route('follow-ups.store') }}" class="space-y-6">
            @csrf
            @include('follow-ups._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Create follow-up</button>
                <a class="btn-secondary" href="{{ route('follow-ups.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
