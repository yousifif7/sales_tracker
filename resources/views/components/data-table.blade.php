@props(['compact' => true])

<div {{ $attributes->merge(['class' => 'table-wrap']) }}>
    <table class="table {{ $compact ? 'table-compact' : '' }}">
        {{ $slot }}
    </table>
</div>
