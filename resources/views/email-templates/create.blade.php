<x-layouts.app title="New Email Template | Sales Tracker" heading="New Email Template" eyebrow="Create outreach template">
    <section class="panel max-w-3xl">
        <form method="post" action="{{ route('email-templates.store') }}" class="space-y-5" data-rich-form>
            @csrf
            @include('email-templates._form', ['template' => $template])
            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Save template</button>
                <a class="btn-secondary" href="{{ route('email-templates.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
