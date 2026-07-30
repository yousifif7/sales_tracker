<x-layouts.app title="Email {{ $contact->name }} | Sales Tracker" heading="Send Email" eyebrow="SMTP outreach">
    <section class="panel mb-6">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-slate-400">Sending to</p>
                <p class="text-lg font-semibold text-white">{{ $contact->name }} &lt;{{ $contact->email }}&gt;</p>
                <p class="text-sm text-slate-400">{{ $contact->company }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can(\App\Support\Permissions::EMAIL_TEMPLATES_VIEW)
                    <a class="btn-secondary" href="{{ route('email-templates.index') }}">Manage templates</a>
                @endcan
            </div>
        </div>
    </section>

    <section class="panel">
        <form method="get" action="{{ route('contacts.email.create', $contact) }}" class="mb-6 grid gap-4 md:grid-cols-[1fr,auto]">
            <div>
                <label class="label" for="template">Load template</label>
                <select class="input" id="template" name="template">
                    <option value="">Blank email</option>
                    @foreach ($templates as $key => $label)
                        <option value="{{ $key }}" @selected((string) $selectedTemplate === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="btn-secondary" type="submit">Apply template</button>
            </div>
        </form>

        <form method="post" action="{{ route('contacts.email.store', $contact) }}" class="space-y-5" data-rich-form>
            @csrf
            <input type="hidden" name="template" value="{{ $selectedTemplate }}">

            <div>
                <label class="label" for="campaign_id">Campaign (optional)</label>
                <select class="input" id="campaign_id" name="campaign_id">
                    <option value="">No campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('campaign_id') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="subject">Subject</label>
                <input class="input" id="subject" name="subject" value="{{ $subject }}" required>
            </div>

            <x-rich-editor
                name="body"
                :value="$body"
                hint="Bold, italic, lists, and links are supported."
            />

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Send via SMTP</button>
                <a class="btn-secondary" href="{{ route('contacts.show', $contact) }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
