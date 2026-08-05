<x-layouts.app title="Bulk Email | Sales Tracker" heading="Send Bulk Email" eyebrow="SMTP outreach">
    <section class="panel mb-6">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-slate-400">Sending to</p>
                <p class="text-lg font-semibold text-white">{{ $recipients->count() }} {{ str('contact')->plural($recipients->count()) }}</p>
                @if ($skippedCount > 0)
                    <p class="mt-1 text-sm text-amber-200">{{ $skippedCount }} selected without an email were skipped.</p>
                @endif
                <p class="mt-2 text-sm text-slate-400">
                    Use <code class="text-slate-300">@{{name}}</code>,
                    <code class="text-slate-300">@{{first_name}}</code>, and
                    <code class="text-slate-300">@{{company}}</code>
                    — they are filled in per contact when each email sends.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can(\App\Support\Permissions::EMAIL_TEMPLATES_VIEW)
                    <a class="btn-secondary" href="{{ route('email-templates.index') }}">Manage templates</a>
                @endcan
            </div>
        </div>

        <div class="mt-4 max-h-48 overflow-y-auto rounded-xl border border-slate-800">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 bg-slate-950 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Company</th>
                        <th class="px-3 py-2 font-medium">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach ($recipients as $recipient)
                        <tr>
                            <td class="px-3 py-2 text-white">{{ $recipient->name ?: '—' }}</td>
                            <td class="px-3 py-2 text-slate-300">{{ $recipient->company ?: '—' }}</td>
                            <td class="px-3 py-2 text-slate-300">{{ $recipient->email }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <form method="post" action="{{ route('contacts.email.bulk.create') }}" class="mb-6 grid gap-4 md:grid-cols-[1fr,auto]">
            @csrf
            @foreach ($recipients as $recipient)
                <input type="hidden" name="contact_ids[]" value="{{ $recipient->id }}">
            @endforeach
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

        <form method="post" action="{{ route('contacts.email.bulk.store') }}" class="space-y-5" data-rich-form>
            @csrf
            @foreach ($recipients as $recipient)
                <input type="hidden" name="contact_ids[]" value="{{ $recipient->id }}">
            @endforeach
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
                hint="Personalization tokens are filled per contact on send. Bold, italic, lists, and links are supported."
            />

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Queue {{ $recipients->count() }} {{ str('email')->plural($recipients->count()) }}</button>
                <a class="btn-secondary" href="{{ route('contacts.index') }}">Cancel</a>
            </div>
        </form>
    </section>
</x-layouts.app>
