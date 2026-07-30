<x-layouts.app title="New Contact | Sales Tracker" heading="New Contact" eyebrow="Create contact">
    <section class="panel">
        <form method="post" action="{{ route('contacts.store') }}" class="space-y-6">
            @csrf
            @include('contacts._form', ['tagString' => old('tags', '')])
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Create contact</button>
                <a class="btn-secondary" href="{{ route('contacts.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
