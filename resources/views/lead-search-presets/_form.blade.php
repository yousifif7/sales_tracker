@props(['preset'])

<div>
    <label class="label" for="name">Prompt name</label>
    <input class="input" id="name" name="name" value="{{ old('name', $preset->name) }}" required>
</div>

<div>
    <label class="label" for="slug">Slug (optional)</label>
    <input class="input" id="slug" name="slug" value="{{ old('slug', $preset->slug) }}" placeholder="auto-from-name">
</div>

<div>
    <label class="label" for="sort_order">Sort order</label>
    <input class="input" id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $preset->sort_order ?? 0) }}">
</div>

<div>
    <label class="label" for="criteria">AI prompt / search criteria</label>
    <textarea class="input min-h-80" id="criteria" name="criteria" required>{{ old('criteria', $preset->criteria) }}</textarea>
    <p class="mt-2 text-xs text-slate-500">This text is sent to OpenRouter when the preset is used on AI Lead Search.</p>
</div>

<label class="mt-2 flex items-center gap-3 text-sm text-slate-300">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $preset->is_active ?? true))>
    Active (shown when starting a new AI search)
</label>
