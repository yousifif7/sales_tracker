<x-layouts.app title="Lead Search Details | Sales Tracker" heading="Lead Search Results" eyebrow="Compact results — open a lead for full details">
    <section class="panel mb-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-sm text-slate-400">
                    {{ $leadSearch->creator?->name ?: 'System' }} • {{ $leadSearch->created_at->toDayDateTimeString() }}
                </p>
                <p class="mt-2 max-h-24 overflow-y-auto whitespace-pre-wrap text-sm text-slate-300">{{ $leadSearch->criteria }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @php
                    $isFailed = (bool) data_get($leadSearch->raw_results, 'failed');
                    $isReady = filled($leadSearch->raw_results) && ! $isFailed;
                @endphp
                <span @class([
                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-emerald-500/15 text-emerald-200' => $isReady,
                    'bg-rose-500/15 text-rose-200' => $isFailed,
                    'bg-amber-500/15 text-amber-200' => ! $isReady && ! $isFailed,
                ])>
                    {{ $isFailed ? 'Failed' : ($isReady ? 'Ready' : 'Still running…') }}
                </span>
                <a class="btn-secondary" href="{{ route('lead-searches.index') }}">Back</a>
                @can(\App\Support\Permissions::LEAD_SEARCHES_DELETE)
                    <form method="post" action="{{ route('lead-searches.destroy', $leadSearch) }}" onsubmit="return confirm('Delete this lead search?')">
                        @csrf
                        @method('delete')
                        <button class="btn-secondary" type="submit">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    </section>

    @if (filled(data_get($leadSearch->raw_results, 'error')))
        <section class="panel mt-6 border-rose-500/30 bg-rose-500/5">
            <h2 class="text-sm font-semibold text-rose-200">Search failed</h2>
            <p class="mt-2 text-sm text-slate-300">{{ data_get($leadSearch->raw_results, 'error') }}</p>
        </section>
    @endif

    @if (filled(data_get($leadSearch->raw_results, 'diagnostics.summary')))
        <section class="panel mt-6 border-amber-500/30 bg-amber-500/5">
            <h2 class="text-sm font-semibold text-amber-200">Why this run is thin</h2>
            <p class="mt-2 text-sm text-slate-300">{{ data_get($leadSearch->raw_results, 'diagnostics.summary') }}</p>
        </section>
    @endif

    <x-data-table wide>
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Quick links</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ((data_get($leadSearch->raw_results, 'results') ?? []) as $lead)
                @php
                    $matchedContact = $matchedContacts[$loop->index] ?? null;
                    $name = filled($lead['name'] ?? null) && strtolower((string) $lead['name']) !== 'null'
                        ? $lead['name']
                        : 'Unknown';
                @endphp
                <tr>
                    <td class="font-medium text-white">{{ $name }}</td>
                    <td>{{ filled($lead['company'] ?? null) ? $lead['company'] : '—' }}</td>
                    <td>{{ filled($lead['role'] ?? null) ? $lead['role'] : '—' }}</td>
                    <td>
                        @if (! empty($lead['email']))
                            <a class="link-action" href="mailto:{{ $lead['email'] }}">{{ $lead['email'] }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ filled($lead['phone'] ?? null) ? $lead['phone'] : '—' }}</td>
                    <td>
                        <div class="flex flex-wrap gap-2 text-xs">
                            @if (! empty($lead['linkedin_url']))
                                <a class="link-action" href="{{ $lead['linkedin_url'] }}" target="_blank" rel="noopener">LinkedIn</a>
                            @endif
                            @if (! empty($lead['website']))
                                <a class="link-action" href="{{ $lead['website'] }}" target="_blank" rel="noopener">Website</a>
                            @endif
                            @foreach (($lead['social_links'] ?? []) as $network => $url)
                                @if (! empty($url))
                                    <a class="link-action" href="{{ $url }}" target="_blank" rel="noopener">{{ str($network)->replace('_', ' ')->title() }}</a>
                                @endif
                            @endforeach
                            @if (empty($lead['linkedin_url']) && empty($lead['website']) && empty($lead['social_links']))
                                —
                            @endif
                        </div>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        @if ($matchedContact)
                            <div class="row-actions">
                                <a class="link-action" href="{{ route('contacts.show', $matchedContact) }}">View</a>
                                @can(\App\Support\Permissions::CONTACTS_UPDATE)
                                    <a class="link-action" href="{{ route('contacts.edit', $matchedContact) }}">Edit</a>
                                @endcan
                                @can(\App\Support\Permissions::EMAILS_SEND)
                                    @if ($matchedContact->email)
                                        <a class="link-action" href="{{ route('contacts.email.create', $matchedContact) }}">Email</a>
                                    @endif
                                @endcan
                                @can(\App\Support\Permissions::CONTACTS_DELETE)
                                    <form method="post" action="{{ route('contacts.destroy', $matchedContact) }}" onsubmit="return confirm('Delete this contact?')" class="inline">
                                        @csrf
                                        @method('delete')
                                        <button class="link-danger" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        @else
                            <span class="text-slate-500">Not imported</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-slate-500">
                        {{ filled($leadSearch->raw_results)
                            ? ((bool) data_get($leadSearch->raw_results, 'failed')
                                ? 'Search failed — see error above.'
                                : 'No leads were returned.')
                            : 'Results will appear once the job finishes. Refresh in a minute.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <details class="panel mt-6">
        <summary class="cursor-pointer text-sm font-semibold text-slate-300">Raw API response (debug)</summary>
        <pre class="mt-4 overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950 p-4 text-xs text-slate-300">{{ json_encode($leadSearch->raw_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre>
    </details>
</x-layouts.app>
