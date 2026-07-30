<x-layouts.app title="Edit Contact | Sales Tracker" heading="Edit Contact" eyebrow="Update contact details">
    <section class="panel">
        <form method="post" action="{{ route('contacts.update', $contact) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('contacts._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save changes</button>
                <a class="btn-secondary" href="{{ route('contacts.show', $contact) }}">Back</a>
            </div>
        </form>
    </section>
</x-layouts.app>
