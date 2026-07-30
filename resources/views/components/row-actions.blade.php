@props([
    'asCell' => true,
])

@if ($asCell)
    <td {{ $attributes->merge(['class' => 'text-right whitespace-nowrap']) }}>
        <div class="row-actions">
            {{ $slot }}
        </div>
    </td>
@else
    <div {{ $attributes->merge(['class' => 'row-actions']) }}>
        {{ $slot }}
    </div>
@endif
