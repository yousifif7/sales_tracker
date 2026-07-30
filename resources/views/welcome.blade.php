<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $contactEmail = config('mail.from.address') ?: 'hello@example.com';
            $pageTitle = 'Sales Tracker | Private AI Outreach CRM for Lead Discovery and Follow-ups';
            $pageDescription = 'Sales Tracker is a modern private outreach CRM for businesses that need AI lead discovery, campaign management, email threading, follow-up tracking, and reporting in one delivered system.';
            $canonicalUrl = url('/');
            $logoUrl = asset('brand/sales-tracker-logo.svg');
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'Sales Tracker',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => $pageDescription,
                'url' => $canonicalUrl,
                'audience' => [
                    '@type' => 'Audience',
                    'audienceType' => 'Businesses selling products or services to target accounts',
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'availability' => 'https://schema.org/InStock',
                    'url' => 'mailto:' . $contactEmail,
                ],
            ];
            $contactHref = 'mailto:' . $contactEmail . '?subject=' . rawurlencode('Sales Tracker demo request');
            $painPoints = [
                [
                    'title' => 'Manual lead hunting wastes selling time',
                    'copy' => 'Stop digging through directories, search engines, and spreadsheets just to build a prospect list.',
                ],
                [
                    'title' => 'Outreach gets scattered across tools',
                    'copy' => 'Keep contacts, campaigns, templates, replies, and reminders connected in one workflow.',
                ],
                [
                    'title' => 'Missed follow-ups cost revenue',
                    'copy' => 'Track opens, responses, and next actions so the pipeline keeps moving instead of going cold.',
                ],
            ];
            $featureCards = [
                [
                    'eyebrow' => 'AI lead search',
                    'title' => 'Find target accounts faster',
                    'copy' => 'Use prompt-based lead discovery to surface businesses and contacts that match your ideal outreach target.',
                ],
                [
                    'eyebrow' => 'CRM workflow',
                    'title' => 'Run campaigns without chaos',
                    'copy' => 'Organize contacts, assign campaigns, manage templates, and keep every touchpoint attached to the right record.',
                ],
                [
                    'eyebrow' => 'Inbox and tracking',
                    'title' => 'See replies, opens, and threads',
                    'copy' => 'Capture outbound and inbound email activity with threading, reply tracking, and follow-up visibility.',
                ],
                [
                    'eyebrow' => 'Reports and permissions',
                    'title' => 'Give teams clarity and control',
                    'copy' => 'Review pipeline activity, monitor performance, and control access with role-based permissions.',
                ],
            ];
            $deliveryPoints = [
                'Delivered as a private business system, not a shared SaaS account.',
                'Can be customized to match your outreach process, team structure, and messaging.',
                'Keeps your data, operations, and deployment under your own control.',
            ];
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="index,follow">
        <meta name="keywords" content="outreach CRM, AI lead discovery, sales prospecting software, lead generation CRM, email follow-up system, private CRM deployment">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="Sales Tracker">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">

        <script type="application/ld+json">
            {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
        <x-assets />
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100">
        <div class="relative overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.2),_transparent_28%),radial-gradient(circle_at_80%_10%,_rgba(99,102,241,0.18),_transparent_26%),linear-gradient(180deg,_rgba(2,6,23,0.2),_rgba(2,6,23,1))]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-400/40 to-transparent"></div>

            <header class="relative z-10">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-6 sm:px-6 lg:px-8">
                    <a href="{{ $canonicalUrl }}" class="shrink-0">
                        <img src="{{ $logoUrl }}" alt="Sales Tracker" class="h-10 w-auto sm:h-12">
                    </a>

                    <div class="hidden items-center gap-8 text-sm text-slate-300 lg:flex">
                        <a href="#features" class="transition hover:text-white">Features</a>
                        <a href="#workflow" class="transition hover:text-white">Workflow</a>
                        <a href="#delivery" class="transition hover:text-white">Delivery</a>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ $contactHref }}" class="btn-secondary">Book demo</a>
                        <a href="{{ route('login') }}" class="btn-primary">Log in</a>
                    </div>
                </div>
            </header>

            <main class="relative z-10">
                <section class="mx-auto max-w-7xl px-4 pb-14 pt-10 sm:px-6 lg:px-8 lg:pb-24 lg:pt-14">
                    <div class="grid gap-14 lg:grid-cols-[1.02fr,0.98fr] lg:items-center">
                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">
                                Private AI-powered outreach CRM
                            </div>

                            <h1 class="mt-8 text-4xl font-semibold leading-[1.02] tracking-tight text-white sm:text-5xl lg:text-7xl">
                                Turn lead discovery, outreach, and follow-up into one clean revenue system.
                            </h1>

                            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300 sm:text-xl">
                                Sales Tracker is a ready-built CRM for businesses that sell products or services and want to stop
                                finding leads manually, losing replies, or managing outreach across scattered tools.
                            </p>

                            <div class="mt-8 flex flex-wrap gap-4">
                                <a href="{{ $contactHref }}" class="btn-primary px-6 py-3 text-base">Book demo</a>
                                <a href="{{ route('login') }}" class="btn-secondary px-6 py-3 text-base">Existing client log in</a>
                            </div>

                            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div class="text-2xl font-semibold text-white">AI</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Prompt-based lead discovery for faster targeting.</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div class="text-2xl font-semibold text-white">Inbox</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Email threads, opens, replies, and follow-up visibility.</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <div class="text-2xl font-semibold text-white">Control</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Private deployment, ownership, and business-specific delivery.</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute -left-6 top-10 h-28 w-28 rounded-full bg-sky-400/20 blur-3xl"></div>
                            <div class="absolute -right-4 bottom-10 h-36 w-36 rounded-full bg-indigo-500/20 blur-3xl"></div>

                            <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/80 shadow-[0_40px_120px_-30px_rgba(14,165,233,0.35)] backdrop-blur">
                                <div class="border-b border-white/10 bg-white/5 px-5 py-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex gap-2">
                                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400/80"></span>
                                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
                                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80"></span>
                                            </div>
                                            <p class="text-sm font-medium text-slate-300">Revenue workflow preview</p>
                                        </div>
                                        <div class="rounded-full border border-sky-400/20 bg-sky-400/10 px-3 py-1 text-xs font-semibold text-sky-200">
                                            Delivered privately
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-5 p-5">
                                    <div class="grid gap-4 sm:grid-cols-[1.1fr,0.9fr]">
                                        <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-200">AI lead search</p>
                                                    <h2 class="mt-3 text-xl font-semibold text-white">Target businesses without manual research</h2>
                                                </div>
                                                <div class="rounded-2xl bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">
                                                    Active
                                                </div>
                                            </div>
                                            <div class="mt-5 space-y-3">
                                                <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-medium text-white">B2B SaaS companies in UAE</p>
                                                        <span class="text-xs text-slate-400">24 leads</span>
                                                    </div>
                                                    <p class="mt-2 text-sm text-slate-400">Prompt matched, enriched, and ready to review.</p>
                                                </div>
                                                <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-sm font-medium text-white">Ecommerce brands needing outreach</p>
                                                        <span class="text-xs text-slate-400">18 leads</span>
                                                    </div>
                                                    <p class="mt-2 text-sm text-slate-400">New target set generated with reusable prompt presets.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-4">
                                            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-200">Inbox status</p>
                                                <div class="mt-4 space-y-3">
                                                    <div class="flex items-center justify-between rounded-2xl bg-slate-950/70 px-4 py-3">
                                                        <span class="text-sm text-slate-300">Opened</span>
                                                        <span class="text-sm font-semibold text-white">42</span>
                                                    </div>
                                                    <div class="flex items-center justify-between rounded-2xl bg-slate-950/70 px-4 py-3">
                                                        <span class="text-sm text-slate-300">Replied</span>
                                                        <span class="text-sm font-semibold text-white">11</span>
                                                    </div>
                                                    <div class="flex items-center justify-between rounded-2xl bg-slate-950/70 px-4 py-3">
                                                        <span class="text-sm text-slate-300">Due follow-ups</span>
                                                        <span class="text-sm font-semibold text-white">7</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-sky-500/20 to-indigo-500/20 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-100">Why teams buy it</p>
                                                <p class="mt-3 text-lg font-semibold text-white">One system to find leads, send outreach, track replies, and keep sales moving.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-200">Contacts</p>
                                            <p class="mt-2 text-sm text-slate-300">Campaign-linked contact records with full history.</p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-200">Templates</p>
                                            <p class="mt-2 text-sm text-slate-300">Reusable outreach messages with edit-friendly content.</p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-200">Permissions</p>
                                            <p class="mt-2 text-sm text-slate-300">Role-based access for teams and managers.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="relative z-10 border-y border-white/5 bg-white/[0.03]">
                    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 text-sm text-slate-300 sm:px-6 lg:grid-cols-3 lg:px-8">
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-4">Built for outbound sales, prospecting, and business development teams.</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-4">Combines AI lead search, CRM workflow, email threading, and reporting.</div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-4">Delivered as your own system, not a shared subscription app.</div>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8" id="features">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-sky-200">What it solves</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">A better system for businesses that sell through outbound outreach.</h2>
                    </div>

                    <div class="mt-10 grid gap-6 lg:grid-cols-3">
                        @foreach ($painPoints as $point)
                            <div class="rounded-[1.75rem] border border-white/10 bg-white/[0.04] p-6">
                                <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-sky-400/20 to-indigo-400/20"></div>
                                <h3 class="mt-5 text-xl font-semibold text-white">{{ $point['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-400">{{ $point['copy'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
                    <div class="grid gap-6 lg:grid-cols-2">
                        @foreach ($featureCards as $card)
                            <div class="rounded-[2rem] border border-white/10 bg-slate-900/65 p-7 shadow-2xl shadow-slate-950/20">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200">{{ $card['eyebrow'] }}</p>
                                <h3 class="mt-4 text-2xl font-semibold text-white">{{ $card['title'] }}</h3>
                                <p class="mt-4 text-sm leading-7 text-slate-400">{{ $card['copy'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8" id="workflow">
                    <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-slate-900 to-slate-950">
                        <div class="grid gap-8 p-8 lg:grid-cols-[0.95fr,1.05fr] lg:p-10">
                            <div class="max-w-xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-sky-200">Core workflow</p>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">From lead discovery to reply handling, all in one view.</h2>
                                <p class="mt-5 text-sm leading-7 text-slate-400">
                                    Sales Tracker is built to reduce admin overhead and give teams a clear operational flow:
                                    find prospects, launch outreach, monitor engagement, and keep follow-ups moving.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                    <p class="text-sm font-semibold text-white">1. Find targets</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Run AI prompt presets for the businesses and buyer profiles you want to reach.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                    <p class="text-sm font-semibold text-white">2. Organize campaigns</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Save leads, assign campaigns, and manage messaging from one CRM workspace.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                    <p class="text-sm font-semibold text-white">3. Track engagement</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">See opened emails, threaded replies, and current outreach status without guessing.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                    <p class="text-sm font-semibold text-white">4. Follow up consistently</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Use reminders, notes, and contact history to keep opportunities from going cold.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8" id="delivery">
                    <div class="grid gap-8 lg:grid-cols-[0.9fr,1.1fr] lg:items-start">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-sky-200">Delivery model</p>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Built for private delivery, ownership, and customization.</h2>
                            <p class="mt-5 text-sm leading-7 text-slate-400">
                                This is not positioned as a self-serve SaaS product. It is sold as a ready-built system
                                that can be delivered for a business as its own outreach operation platform.
                            </p>
                        </div>

                        <div class="grid gap-4">
                            @foreach ($deliveryPoints as $point)
                                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                    <div class="flex gap-4">
                                        <div class="mt-1 h-3 w-3 shrink-0 rounded-full bg-sky-300"></div>
                                        <p class="text-sm leading-7 text-slate-300">{{ $point }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
                    <div class="relative overflow-hidden rounded-[2.25rem] border border-sky-400/20 bg-gradient-to-br from-sky-500/15 via-slate-900 to-indigo-500/15 px-6 py-12 text-center sm:px-10 lg:py-16">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-300/60 to-transparent"></div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-sky-200">Ready to sell smarter?</p>
                        <h2 class="mx-auto mt-5 max-w-3xl text-3xl font-semibold tracking-tight text-white sm:text-4xl lg:text-5xl">
                            Get a modern outreach CRM that helps your business find leads, manage outreach, and close with more control.
                        </h2>
                        <p class="mx-auto mt-5 max-w-2xl text-sm leading-7 text-slate-300">
                            Book a demo to discuss delivery, customization, and fit. Existing users can log in to access the CRM immediately.
                        </p>
                        <div class="mt-8 flex flex-wrap justify-center gap-4">
                            <a href="{{ $contactHref }}" class="btn-primary px-6 py-3 text-base">Book demo</a>
                            <a href="{{ route('login') }}" class="btn-secondary px-6 py-3 text-base">Log in</a>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
