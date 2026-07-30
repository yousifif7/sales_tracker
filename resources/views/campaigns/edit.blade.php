<x-layouts.app title="Edit Campaign | Sales Tracker" heading="Edit Campaign" eyebrow="Update campaign">
    <section class="panel">
        <form method="post" action="{{ route('campaigns.update', $campaign) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('campaigns._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save changes</button>
                <a class="btn-secondary" href="{{ route('campaigns.show', $campaign) }}">Back</a>
            </div>
        </form>
    </section>
</x-layouts.app>
