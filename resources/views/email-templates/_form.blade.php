@props(['template'])

<div>
    <label class="label" for="name">Template name</label>
    <input class="input" id="name" name="name" value="{{ old('name', $template->name) }}" required>
</div>

<div>
    <label class="label" for="slug">Slug (optional)</label>
    <input class="input" id="slug" name="slug" value="{{ old('slug', $template->slug) }}" placeholder="auto-from-name">
    <p class="mt-1 text-xs text-slate-500">Used when loading this template on the send-email screen.</p>
</div>

<div>
    <label class="label" for="subject">Subject</label>
    <input class="input" id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required>
    <p class="mt-1 text-xs text-slate-500">
        Tokens:
        <code class="text-sky-300">@{{first_name}}</code>,
        <code class="text-sky-300">@{{company}}</code>,
        <code class="text-sky-300">@{{name}}</code>
    </p>
</div>

<x-rich-editor
    name="body"
    :value="old('body', $template->body)"
    :hint="'Use tokens like {{first_name}}, {{company}}, {{name}} in the body too.'"
/>

<label class="mt-4 flex items-center gap-3 text-sm text-slate-300">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))>
    Active (available when composing emails)
</label>
