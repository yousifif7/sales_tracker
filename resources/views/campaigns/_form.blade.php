<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="label" for="name">Name</label>
        <input class="input" id="name" name="name" value="{{ old('name', $campaign->name) }}" required>
    </div>
    <div>
        <label class="label" for="channel">Channel</label>
        <select class="input" id="channel" name="channel" required>
            @foreach ($channelOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('channel', $campaign->channel?->value ?? $campaign->channel) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="start_date">Start date</label>
        <input class="input" id="start_date" name="start_date" type="date" value="{{ old('start_date', optional($campaign->start_date)->format('Y-m-d')) }}">
    </div>
    <div class="md:col-span-2">
        <label class="label" for="notes">Notes</label>
        <textarea class="input min-h-36" id="notes" name="notes">{{ old('notes', $campaign->notes) }}</textarea>
    </div>
</div>
