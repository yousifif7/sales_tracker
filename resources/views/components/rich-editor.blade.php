@props([
    'name' => 'body',
    'value' => '',
    'id' => null,
    'label' => 'Body',
    'hint' => null,
])

@php
    $editorId = $id ?: $name.'_editor';
    $inputId = $id ?: $name;
@endphp

<div
    class="rich-editor"
    data-rich-editor
    x-data
>
    <div class="flex items-center justify-between gap-3">
        <label class="label mb-0" for="{{ $editorId }}">{{ $label }}</label>
        @if ($hint)
            <p class="text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>

    <div class="rich-toolbar" role="toolbar" aria-label="Formatting">
        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
        <span class="rich-sep"></span>
        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
        <span class="rich-sep"></span>
        <button type="button" data-cmd="createLink" title="Insert link">Link</button>
        <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
        <span class="rich-sep"></span>
        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
    </div>

    <div
        id="{{ $editorId }}"
        class="rich-surface input"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
    >{!! $value !!}</div>

    <textarea class="hidden" id="{{ $inputId }}" name="{{ $name }}" required>{{ $value }}</textarea>
</div>
