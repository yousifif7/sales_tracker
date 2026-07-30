<x-layouts.app title="New Role | Sales Tracker" heading="New Role" eyebrow="Create access role">
    <section class="panel">
        <form method="post" action="{{ route('roles.store') }}" class="space-y-6">
            @csrf
            @include('roles._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Create role</button>
                <a class="btn-secondary" href="{{ route('roles.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
