@props([
    'step' => null,
])

@php
    use App\Enums\EmailSequenceNextStep;

    $classes = match ($step) {
        EmailSequenceNextStep::Followup => 'bg-sky-500/15 text-sky-200 ring-sky-500/30',
        EmailSequenceNextStep::Nudge => 'bg-amber-500/15 text-amber-200 ring-amber-500/30',
        EmailSequenceNextStep::Exit => 'bg-violet-500/15 text-violet-200 ring-violet-500/30',
        default => 'bg-slate-800/80 text-slate-400 ring-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$classes}"]) }}>
    {{ $step?->label() ?: '—' }}
</span>
