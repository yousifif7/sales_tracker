<x-layouts.app title="New Campaign | Sales Tracker" heading="New Campaign" eyebrow="Create campaign">
    <section class="panel">
        <form method="post" action="{{ route('campaigns.store') }}" class="space-y-6">
            @csrf
            @include('campaigns._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Create campaign</button>
                <a class="btn-secondary" href="{{ route('campaigns.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
