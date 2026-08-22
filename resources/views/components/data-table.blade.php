@props([
    'compact' => true,
    'wide' => false,
])

<div {{ $attributes->merge(['class' => 'table-wrap min-w-0 max-w-full']) }}>
    <div class="table-scroll max-w-full">
        <table @class([
            'table',
            'table-compact' => $compact,
            'table-wide' => $wide,
        ])>
            {{ $slot }}
        </table>
    </div>
</div>
