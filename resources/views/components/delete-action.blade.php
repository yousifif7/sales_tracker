@props([
    'action',
    'confirm' => 'Delete this item?',
    'label' => 'Delete',
    'permission' => null,
])

@php
    $allowed = blank($permission) || auth()->user()?->can($permission);
@endphp

@if ($allowed)
    <form method="post" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
        @csrf
        @method('delete')
        <button class="link-danger" type="submit">{{ $label }}</button>
    </form>
@endif
