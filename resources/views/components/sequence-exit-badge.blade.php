@props([
    'reason' => null,
])

@php
    use App\Enums\EmailSequenceExitReason;

    $classes = match ($reason) {
        EmailSequenceExitReason::Replied => 'bg-emerald-500/15 text-emerald-200 ring-emerald-500/30',
        EmailSequenceExitReason::HotOpens => 'bg-cyan-500/15 text-cyan-200 ring-cyan-500/30',
        EmailSequenceExitReason::QuietLost => 'bg-rose-500/15 text-rose-200 ring-rose-500/30',
        EmailSequenceExitReason::Cancelled => 'bg-slate-700/80 text-slate-300 ring-slate-600',
        EmailSequenceExitReason::StatusChanged => 'bg-violet-500/15 text-violet-200 ring-violet-500/30',
        EmailSequenceExitReason::MissingTemplate, EmailSequenceExitReason::SendFailed => 'bg-red-500/15 text-red-200 ring-red-500/30',
        default => 'bg-slate-800/80 text-slate-400 ring-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$classes}"]) }}>
    {{ $reason?->label() ?: 'Completed' }}
</span>
