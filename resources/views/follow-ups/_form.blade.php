<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="label" for="contact_id">Contact</label>
        <select class="input" id="contact_id" name="contact_id" required>
            <option value="">Select contact</option>
            @foreach ($contacts as $contact)
                <option value="{{ $contact->id }}" @selected((string) old('contact_id', $followUp->contact_id ?? request('contact_id')) === (string) $contact->id)>
                    {{ $contact->name }}{{ $contact->company ? ' - '.$contact->company : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="due_date">Due date</label>
        <input class="input" id="due_date" name="due_date" type="date" value="{{ old('due_date', optional($followUp->due_date)->format('Y-m-d')) }}" required>
    </div>
    <div class="flex items-end">
        <label class="mt-2 inline-flex items-center gap-3 rounded-2xl border border-slate-700 px-4 py-3 text-sm text-slate-200">
            <input type="checkbox" name="completed" value="1" @checked(old('completed', $followUp->completed))>
            Mark completed
        </label>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="note">Note</label>
        <textarea class="input min-h-32" id="note" name="note" required>{{ old('note', $followUp->note) }}</textarea>
    </div>
</div>
