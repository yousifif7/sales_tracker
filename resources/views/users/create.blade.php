<x-layouts.app title="New User | Sales Tracker" heading="New User" eyebrow="Create team member">
    <section class="panel">
        <form method="post" action="{{ route('users.store') }}" class="space-y-6">
            @csrf
            @include('users._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Create user</button>
                <a class="btn-secondary" href="{{ route('users.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
