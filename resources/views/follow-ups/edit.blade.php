<x-layouts.app title="Edit Follow-up | Sales Tracker" heading="Edit Follow-up" eyebrow="Update reminder">
    <section class="panel">
        <form method="post" action="{{ route('follow-ups.update', $followUp) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('follow-ups._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save changes</button>
                <a class="btn-secondary" href="{{ route('follow-ups.index') }}">Back</a>
            </div>
        </form>
    </section>
</x-layouts.app>
