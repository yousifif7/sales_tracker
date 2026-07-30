<x-layouts.app title="Edit Role | Sales Tracker" heading="Edit Role" eyebrow="Update role permissions">
    <section class="panel">
        <form method="post" action="{{ route('roles.update', $role) }}" class="space-y-6">
            @csrf
            @method('put')
            @include('roles._form')
            <div class="flex gap-3">
                <button class="btn-primary" type="submit">Save permissions</button>
                <a class="btn-secondary" href="{{ route('roles.index') }}">Back</a>
            </div>
        </form>
    </section>
</x-layouts.app>
