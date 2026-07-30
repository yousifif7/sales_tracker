<x-layouts.app title="Edit Interaction | Sales Tracker" heading="Edit Interaction" eyebrow="Update outreach record">
    <section class="panel">
        <form method="post" action="{{ route('interactions.update', $interaction) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('interactions._form')
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Save changes</button>
                <a class="btn-secondary" href="{{ route('contacts.show', $interaction->contact) }}">Back to contact</a>
            </div>
        </form>
    </section>
</x-layouts.app>
