@props([
    'links' => [],
])

@if (count($links))
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach ($links as $link)
            <a
                href="{{ $link['url'] }}"
                @if (! str_starts_with($link['url'], 'mailto:'))
                    target="_blank" rel="noopener"
                @endif
                @class([
                    'inline-flex items-center rounded-xl px-3 py-2 text-sm font-semibold transition',
                    'bg-sky-500 text-slate-950 hover:bg-sky-400' => ($link['type'] ?? '') === 'email',
                    'bg-[#0a66c2]/20 text-sky-200 ring-1 ring-[#0a66c2]/40 hover:bg-[#0a66c2]/30' => ($link['type'] ?? '') === 'linkedin' || ($link['type'] ?? '') === 'company_linkedin',
                    'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/30 hover:bg-emerald-500/25' => ($link['type'] ?? '') === 'website',
                    'bg-slate-800 text-slate-200 ring-1 ring-slate-700 hover:bg-slate-700' => ! in_array(($link['type'] ?? ''), ['email', 'linkedin', 'company_linkedin', 'website'], true),
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
@endif
