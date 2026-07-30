<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="label" for="name">Name</label>
        <input class="input" id="name" name="name" value="{{ old('name', $contact->name) }}" required>
    </div>
    <div>
        <label class="label" for="company">Company</label>
        <input class="input" id="company" name="company" value="{{ old('company', $contact->company) }}">
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email', $contact->email) }}">
    </div>
    <div>
        <label class="label" for="phone">Phone</label>
        <input class="input" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}">
    </div>
    <div>
        <label class="label" for="source">Source</label>
        <select class="input" id="source" name="source" required>
            @foreach ($sourceOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('source', $contact->source?->value ?? $contact->source) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="status">Status</label>
        <select class="input" id="status" name="status" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $contact->status?->value ?? $contact->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="website">Company website</label>
        <input class="input" id="website" name="website" type="url" value="{{ old('website', $contact->website) }}" placeholder="https://company.com">
    </div>
    <div class="md:col-span-2">
        <label class="label" for="linkedin_url">LinkedIn (person)</label>
        <input class="input" id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $contact->linkedin_url) }}" placeholder="https://www.linkedin.com/in/...">
    </div>
    <div class="md:col-span-2">
        <label class="label" for="source_url">Source URL</label>
        <input class="input" id="source_url" name="source_url" type="url" value="{{ old('source_url', $contact->source_url) }}">
    </div>
    <div class="md:col-span-2">
        <label class="label" for="tags">Tags</label>
        <input class="input" id="tags" name="tags" value="{{ old('tags', $tagString ?? '') }}" placeholder="enterprise, retail, hot lead">
        <p class="mt-2 text-sm text-slate-500">Separate tags with commas.</p>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="notes">Notes</label>
        <textarea class="input min-h-36" id="notes" name="notes">{{ old('notes', $contact->notes) }}</textarea>
    </div>
</div>
