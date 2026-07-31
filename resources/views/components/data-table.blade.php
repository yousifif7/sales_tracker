@props([
    'compact' => true,
    'wide' => false,
])

<div {{ $attributes->merge(['class' => 'table-wrap']) }}>
    <div class="table-scroll">
        <table @class([
            'table',
            'table-compact' => $compact,
            'table-wide' => $wide,
        ])>
            {{ $slot }}
        </table>
    </div>
</div>
