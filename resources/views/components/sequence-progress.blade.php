@props([
    'enrollment',
])

@php
    $steps = [
        ['key' => 'cold', 'label' => 'Cold', 'short' => 'Cold', 'done' => (bool) $enrollment->enrolled_at],
        ['key' => 'followup', 'label' => 'Follow-up', 'short' => 'FU', 'done' => (bool) $enrollment->followup_sent_at],
        ['key' => 'nudge', 'label' => 'Nudge', 'short' => 'Nudge', 'done' => (bool) $enrollment->nudge_sent_at],
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 flex-nowrap items-stretch overflow-hidden rounded-lg border border-slate-700/80 text-[11px] font-semibold leading-none']) }}
    role="list"
    aria-label="Sequence progress"
>
    @foreach ($steps as $index => $step)
        <span
            role="listitem"
            title="{{ $step['label'] }}{{ $step['done'] ? ' — sent' : ' — pending' }}"
            @class([
                'inline-flex items-center gap-1 px-2.5 py-1.5 whitespace-nowrap',
                'border-r border-slate-700/80' => $index < count($steps) - 1,
                'bg-emerald-500/20 text-emerald-100' => $step['done'],
                'bg-slate-900/80 text-slate-500' => ! $step['done'],
            ])
        >
            @if ($step['done'])
                <svg class="h-3 w-3 shrink-0 text-emerald-300" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                    <path d="M2.5 6.25 5 8.75 9.5 3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @else
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-slate-600" aria-hidden="true"></span>
            @endif
            <span class="hidden sm:inline">{{ $step['label'] }}</span>
            <span class="sm:hidden">{{ $step['short'] }}</span>
        </span>
    @endforeach
</div>
