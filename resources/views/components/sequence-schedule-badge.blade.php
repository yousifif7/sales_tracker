@props([
    'nextActionAt' => null,
])

@php
    $timezone = (string) config('outreach.sequence.timezone', 'Europe/London');
    $at = $nextActionAt?->timezone($timezone);
    $now = now()->timezone($timezone);

    $isDue = $at && $at->lte($now);
    $isLaterToday = $at && ! $isDue && $at->isSameDay($now);

    [$classes, $label] = match (true) {
        $isDue => ['bg-amber-500/20 text-amber-100 ring-amber-500/40', 'Due now'],
        $isLaterToday => ['bg-orange-500/15 text-orange-200 ring-orange-500/30', 'Due today'],
        $at?->isFuture() => ['bg-sky-500/15 text-sky-200 ring-sky-500/30', 'Scheduled'],
        default => ['bg-slate-800/80 text-slate-400 ring-slate-700', 'Waiting'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {$classes}"]) }}>
    {{ $label }}
</span>
