<x-layouts.app title="Follow-ups | Sales Tracker" heading="Follow-ups" eyebrow="Reminders and next steps">
    <section class="panel mb-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <select class="input mt-0" name="scope">
                <option value="">All dates</option>
                <option value="today" @selected(request('scope') === 'today')>Due today</option>
                <option value="week" @selected(request('scope') === 'week')>Due this week</option>
            </select>
            <select class="input mt-0" name="completed">
                <option value="">All statuses</option>
                <option value="0" @selected(request('completed') === '0')>Open</option>
                <option value="1" @selected(request('completed') === '1')>Completed</option>
            </select>
            <div class="filter-actions sm:col-span-2 lg:col-span-2">
                <button class="btn-primary" type="submit">Filter</button>
                @can(\App\Support\Permissions::FOLLOW_UPS_CREATE)
                    <a class="btn-secondary" href="{{ route('follow-ups.create') }}">New follow-up</a>
                @endcan
            </div>
        </form>
    </section>

    <x-data-table>
        <thead>
            <tr>
                <th>Contact</th>
                <th>Due</th>
                <th>Note</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
            @forelse ($followUps as $followUp)
                <tr>
                    <td class="font-medium text-white">{{ $followUp->contact?->name ?: '—' }}</td>
                    <td class="whitespace-nowrap">{{ $followUp->due_date->format('M d, Y') }}</td>
                    <td class="max-w-md truncate" title="{{ $followUp->note }}">{{ \Illuminate\Support\Str::limit($followUp->note, 80) }}</td>
                    <td>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-semibold',
                            'bg-emerald-500/15 text-emerald-200' => $followUp->completed,
                            'bg-amber-500/15 text-amber-200' => ! $followUp->completed,
                        ])>
                            {{ $followUp->completed ? 'Done' : 'Open' }}
                        </span>
                    </td>
                    <x-row-actions>
                        @if ($followUp->contact)
                            <a class="link-action" href="{{ route('contacts.show', $followUp->contact) }}">View</a>
                        @endif
                        @can(\App\Support\Permissions::FOLLOW_UPS_UPDATE)
                            <a class="link-action" href="{{ route('follow-ups.edit', $followUp) }}">Edit</a>
                            <form method="post" action="{{ route('follow-ups.toggle', $followUp) }}" class="inline">
                                @csrf
                                @method('patch')
                                <button class="link-action" type="submit">{{ $followUp->completed ? 'Reopen' : 'Done' }}</button>
                            </form>
                        @endcan
                        <x-delete-action
                            :action="route('follow-ups.destroy', $followUp)"
                            :permission="\App\Support\Permissions::FOLLOW_UPS_DELETE"
                            confirm="Delete this follow-up?"
                        />
                    </x-row-actions>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500">No follow-ups scheduled yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <div class="mt-6">
        {{ $followUps->links() }}
    </div>
</x-layouts.app>
