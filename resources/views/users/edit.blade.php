<x-layouts.app title="Edit User | Sales Tracker" heading="Edit User" eyebrow="Update team member">
    <section class="panel">
        <form method="post" action="{{ route('users.update', $user) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('users._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save changes</button>
                <a class="btn-secondary" href="{{ route('users.index') }}">Back</a>
            </div>
        </form>
    </section>
</x-layouts.app>
