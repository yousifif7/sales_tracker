<x-layouts.app title="Edit Email Template | Sales Tracker" heading="Edit Email Template" eyebrow="{{ $template->name }}">
    <section class="panel max-w-3xl">
        <form method="post" action="{{ route('email-templates.update', $template) }}" class="space-y-5" data-rich-form>
            @csrf
            @method('put')
            @include('email-templates._form', ['template' => $template])
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Update template</button>
                <a class="btn-secondary" href="{{ route('email-templates.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
