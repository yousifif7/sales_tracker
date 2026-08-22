<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $contactEmail = 'contact@yousiffarra.com';
            $pageTitle = 'Sales Tracker | Outreach CRM with AI Lead Search & Email Sequences';
            $pageDescription = 'A Laravel outreach CRM built for B2B sales: UK-focused AI lead discovery, tracked cold email, automated follow-up sequences on UK business days, and live pipeline analytics.';
            $canonicalUrl = url('/');
            $logoUrl = asset('brand/sales-tracker-logo.svg');
            $portfolioUrl = 'https://yousiffarra.com';
            $fieldlineUrl = config('outreach.signature.website', 'https://fieldline-wf.com');
            $structuredData = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'Sales Tracker',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => $pageDescription,
                'url' => $canonicalUrl,
                'author' => ['@type' => 'Person', 'name' => config('outreach.signature.name', 'Yousif Elfarra'), 'url' => $portfolioUrl],
                'offers' => ['@type' => 'Offer', 'availability' => 'https://schema.org/InStock', 'url' => 'mailto:'.$contactEmail],
            ];
            $contactHref = 'mailto:'.$contactEmail.'?subject='.rawurlencode('Sales Tracker — demo or delivery enquiry');
            $featureCards = [
                ['title' => 'AI lead search', 'copy' => 'UK-focused prompts, regional presets, deduped import with phone and LinkedIn.'],
                ['title' => 'Email sequences', 'copy' => 'Follow-up day 4, nudge day 8, exit day 15 — with manual override and bulk ops.'],
                ['title' => 'Live reports', 'copy' => 'Open rate, funnel, hottest opens, multi-touch — from real sent mail.'],
                ['title' => 'CRM & inbox', 'copy' => 'Contacts, templates, threaded replies, roles, and follow-up tasks.'],
            ];
            $workflowSteps = [
                ['title' => 'Discover', 'copy' => 'AI search → review → import clean UK leads.'],
                ['title' => 'Outreach', 'copy' => 'Tracked cold email + sequence enrollment.'],
                ['title' => 'Automate', 'copy' => 'UK weekday cron, or pause / retry anytime.'],
                ['title' => 'Convert', 'copy' => 'Hot opens → LinkedIn / video → qualified.'],
            ];
            $techStack = ['Laravel 11', 'Tailwind', 'MySQL', 'Cron & queues', 'OpenRouter', 'Open tracking'];
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        {{-- Landing page uses self-contained CSS so it renders correctly without rebuilding Vite assets. --}}
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            html { overflow-x: hidden; }
            body.landing-page {
                margin: 0;
                min-height: 100vh;
                background: #070b14;
                color: #e2e8f0;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                font-size: 16px;
                line-height: 1.5;
                -webkit-font-smoothing: antialiased;
                font-feature-settings: "ss01", "cv01";
            }
            .landing-page a { color: inherit; text-decoration: none; }
            .landing-page img { display: block; max-width: 100%; height: auto; }
            .landing-glow {
                min-height: 100vh;
                background:
                    radial-gradient(ellipse 80% 50% at 50% -20%, rgba(56, 189, 248, 0.12), transparent),
                    radial-gradient(ellipse 60% 40% at 100% 0%, rgba(99, 102, 241, 0.08), transparent);
            }
            .landing-container {
                width: 100%;
                max-width: 72rem;
                margin-left: auto;
                margin-right: auto;
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }
            .landing-container--narrow { max-width: 64rem; }
            .landing-nav {
                position: sticky;
                top: 0;
                z-index: 50;
                background: rgba(7, 11, 20, 0.72);
                backdrop-filter: blur(12px);
                border-bottom: 1px solid rgba(148, 163, 184, 0.08);
            }
            .landing-nav__inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
            .landing-nav__logo { flex-shrink: 0; opacity: 0.95; transition: opacity 0.2s; }
            .landing-nav__logo:hover { opacity: 1; }
            .landing-nav__logo img { height: 2.25rem; width: auto; }
            .landing-nav__links {
                display: none;
                align-items: center;
                gap: 1.75rem;
                font-size: 0.875rem;
                color: #94a3b8;
            }
            .landing-nav__links a { transition: color 0.2s; }
            .landing-nav__links a:hover { color: #fff; }
            .landing-nav__actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .landing-nav__portfolio {
                display: none;
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                color: #94a3b8;
                border-radius: 0.5rem;
                transition: background 0.2s, color 0.2s;
            }
            .landing-nav__portfolio:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
            .landing-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.75rem;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                font-weight: 600;
                transition: background 0.2s, border-color 0.2s, color 0.2s;
                white-space: nowrap;
            }
            .landing-btn--primary {
                background: #0ea5e9;
                color: #020617;
            }
            .landing-btn--primary:hover { background: #38bdf8; }
            .landing-btn--secondary {
                border: 1px solid #334155;
                background: #0f172a;
                color: #f1f5f9;
            }
            .landing-btn--secondary:hover { border-color: #475569; background: #1e293b; }
            .landing-btn--lg { padding: 0.75rem 1.5rem; border-radius: 0.75rem; }
            .landing-hero {
                padding-top: 3.5rem;
                padding-bottom: 4rem;
                text-align: center;
            }
            .landing-eyebrow {
                color: #7dd3fc;
                font-size: 0.8125rem;
                font-weight: 500;
                margin: 0;
            }
            .landing-hero__title {
                margin: 1.25rem auto 0;
                max-width: 48rem;
                font-size: clamp(2rem, 5vw, 3.25rem);
                font-weight: 600;
                line-height: 1.12;
                letter-spacing: -0.02em;
                color: #fff;
            }
            .landing-hero__lead {
                margin: 1.5rem auto 0;
                max-width: 42rem;
                font-size: 1.0625rem;
                line-height: 1.65;
                color: #94a3b8;
            }
            .landing-hero__lead a {
                color: rgba(125, 211, 252, 0.9);
                text-decoration: underline;
                text-decoration-color: rgba(56, 189, 248, 0.3);
                text-underline-offset: 3px;
            }
            .landing-hero__lead a:hover { color: #bae6fd; }
            .landing-hero__actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                margin-top: 2.25rem;
            }
            .landing-pills {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
                margin: 2.5rem auto 0;
                max-width: 42rem;
            }
            .landing-pill {
                background: rgba(148, 163, 184, 0.06);
                border: 1px solid rgba(148, 163, 184, 0.1);
                border-radius: 9999px;
                padding: 0.375rem 1rem;
                font-size: 0.8125rem;
                font-weight: 500;
                color: #cbd5e1;
            }
            .landing-preview-wrap { padding-bottom: 5rem; }
            .landing-preview {
                overflow: hidden;
                border-radius: 1rem;
                background: linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(10, 15, 28, 0.98) 100%);
                border: 1px solid rgba(148, 163, 184, 0.12);
                box-shadow:
                    0 0 0 1px rgba(255, 255, 255, 0.03) inset,
                    0 24px 80px -24px rgba(0, 0, 0, 0.65),
                    0 0 120px -40px rgba(56, 189, 248, 0.15);
            }
            .landing-preview__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }
            .landing-preview__brand {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-width: 0;
            }
            .landing-preview__icon {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 0.5rem;
                background: rgba(14, 165, 233, 0.15);
                font-size: 0.75rem;
                font-weight: 700;
                color: #7dd3fc;
            }
            .landing-preview__brand p { margin: 0; }
            .landing-preview__brand-title { font-size: 0.875rem; font-weight: 500; color: #fff; }
            .landing-preview__brand-sub { font-size: 0.75rem; color: #94a3b8; }
            .landing-preview__tabs {
                display: none;
                align-items: center;
                gap: 1rem;
                font-size: 0.75rem;
                color: #94a3b8;
                flex-shrink: 0;
            }
            .landing-preview__tabs .is-active { color: #fff; }
            .landing-stats {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }
            .landing-stat {
                padding: 1.25rem 1rem;
                background: rgba(255, 255, 255, 0.03);
            }
            .landing-stat:nth-child(odd) { border-right: 1px solid rgba(255, 255, 255, 0.06); }
            .landing-stat:nth-child(-n+2) { border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
            .landing-stat__label { margin: 0; font-size: 0.75rem; color: #94a3b8; }
            .landing-stat__value {
                margin: 0.375rem 0 0;
                font-size: 1.75rem;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
                color: #fff;
            }
            .landing-stat__value--green { color: rgba(52, 211, 153, 0.9); }
            .landing-stat__value--sky { color: rgba(56, 189, 248, 0.9); }
            .landing-preview__grid {
                display: grid;
                gap: 1px;
                background: rgba(255, 255, 255, 0.04);
            }
            .landing-preview__panel {
                background: #0a101c;
                padding: 1.25rem;
            }
            .landing-preview__panel-title { margin: 0; font-size: 0.875rem; font-weight: 500; color: #fff; }
            .landing-preview__panel-sub { margin: 0.25rem 0 0; font-size: 0.75rem; color: #94a3b8; }
            .landing-preview__list { list-style: none; margin: 1rem 0 0; padding: 0; }
            .landing-preview__list li + li { margin-top: 0.625rem; }
            .landing-preview__row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                border-radius: 0.75rem;
                background: rgba(255, 255, 255, 0.03);
            }
            .landing-preview__row--plain { padding: 0; background: none; border-radius: 0; }
            .landing-preview__row-text { min-width: 0; text-align: left; }
            .landing-preview__row-text p { margin: 0; }
            .landing-preview__name { font-size: 0.875rem; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .landing-preview__company { font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .landing-badge {
                flex-shrink: 0;
                border-radius: 9999px;
                padding: 0.125rem 0.625rem;
                font-size: 0.75rem;
                font-weight: 500;
            }
            .landing-badge--amber { background: rgba(251, 191, 36, 0.1); color: rgba(253, 224, 171, 0.9); }
            .landing-badge--sky { background: rgba(56, 189, 248, 0.1); color: rgba(186, 230, 253, 0.9); }
            .landing-badge--green { color: rgba(52, 211, 153, 0.9); font-size: 0.875rem; font-weight: 500; }
            .landing-caption {
                margin-top: 1rem;
                text-align: center;
                font-size: 0.75rem;
                color: #94a3b8;
            }
            .landing-section { padding-top: 4rem; padding-bottom: 4rem; }
            .landing-section__head {
                max-width: 42rem;
                margin: 0 auto;
                text-align: center;
            }
            .landing-section__title {
                margin: 0.75rem 0 0;
                font-size: clamp(1.75rem, 4vw, 2.25rem);
                font-weight: 600;
                letter-spacing: -0.02em;
                color: #fff;
            }
            .landing-section__lead {
                margin: 1rem 0 0;
                font-size: 0.9375rem;
                line-height: 1.65;
                color: #94a3b8;
            }
            .landing-cards {
                display: grid;
                gap: 1rem;
                margin-top: 3rem;
            }
            .landing-card {
                background: rgba(15, 23, 42, 0.45);
                border: 1px solid rgba(148, 163, 184, 0.08);
                border-radius: 1rem;
                padding: 1.5rem;
                transition: border-color 0.2s, background 0.2s;
            }
            .landing-card:hover {
                border-color: rgba(56, 189, 248, 0.18);
                background: rgba(15, 23, 42, 0.65);
            }
            .landing-card__title { margin: 0; font-size: 1.125rem; font-weight: 600; color: #fff; }
            .landing-card__copy { margin: 0.5rem 0 0; font-size: 0.875rem; line-height: 1.65; color: #94a3b8; }
            .landing-workflow {
                border-radius: 1.5rem;
                padding: 2rem;
            }
            .landing-workflow__grid {
                display: grid;
                gap: 2.5rem;
            }
            .landing-steps { list-style: none; margin: 0; padding: 0; }
            .landing-steps li + li { margin-top: 0.75rem; }
            .landing-step {
                display: flex;
                gap: 1rem;
                padding: 1rem;
                border-radius: 0.75rem;
                background: rgba(255, 255, 255, 0.02);
            }
            .landing-step__num {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 9999px;
                background: rgba(14, 165, 233, 0.1);
                font-size: 0.875rem;
                font-weight: 600;
                color: #7dd3fc;
            }
            .landing-step__title { margin: 0; font-weight: 500; color: #fff; }
            .landing-step__copy { margin: 0.125rem 0 0; font-size: 0.875rem; color: #94a3b8; }
            .landing-split {
                display: grid;
                gap: 1.25rem;
                padding-bottom: 4rem;
            }
            .landing-cta {
                border-radius: 1.5rem;
                padding: 3rem 1.5rem;
                text-align: center;
                background: linear-gradient(180deg, rgba(14, 165, 233, 0.08), transparent);
            }
            .landing-cta__actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.75rem;
                margin-top: 1.75rem;
            }
            .landing-footer {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-top: 3rem;
                padding-top: 2rem;
                border-top: 1px solid rgba(255, 255, 255, 0.06);
                font-size: 0.8125rem;
                color: #94a3b8;
            }
            .landing-footer a:hover { color: #cbd5e1; }
            .landing-tag-row { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1.25rem; }
            .landing-tag {
                background: rgba(148, 163, 184, 0.06);
                border: 1px solid rgba(148, 163, 184, 0.1);
                border-radius: 0.5rem;
                padding: 0.375rem 0.75rem;
                font-size: 0.75rem;
                font-weight: 500;
                color: #cbd5e1;
            }
            @media (min-width: 640px) {
                .landing-container { padding-left: 2rem; padding-right: 2rem; }
                .landing-nav__portfolio { display: inline-block; }
                .landing-hero { padding-top: 5rem; }
                .landing-preview { border-radius: 1.5rem; }
                .landing-preview__header { padding: 1rem 1.5rem; }
                .landing-preview__tabs { display: flex; }
                .landing-stat { padding: 1.5rem; }
                .landing-stat__value { font-size: 1.875rem; }
                .landing-preview__panel { padding: 1.5rem; }
                .landing-card { padding: 1.75rem; border-radius: 1rem; }
                .landing-workflow { padding: 2.5rem; }
                .landing-footer { flex-direction: row; font-size: 0.875rem; }
            }
            @media (min-width: 768px) {
                .landing-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
                .landing-preview__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (min-width: 1024px) {
                .landing-nav__links { display: flex; }
                .landing-hero { padding-top: 6rem; }
                .landing-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
                .landing-stat { border-right: 1px solid rgba(255, 255, 255, 0.06); border-bottom: none !important; }
                .landing-stat:last-child { border-right: none; }
                .landing-workflow__grid { grid-template-columns: 1fr 1fr; align-items: center; }
                .landing-split { grid-template-columns: 1fr 1fr; }
            }
        </style>
    </head>
    <body class="landing-page">
        <div class="landing-glow">
            <header class="landing-nav">
                <div class="landing-container landing-nav__inner">
                    <a href="{{ $canonicalUrl }}" class="landing-nav__logo">
                        <img src="{{ $logoUrl }}" alt="Sales Tracker">
                    </a>
                    <nav class="landing-nav__links" aria-label="Page sections">
                        <a href="#features">Features</a>
                        <a href="#preview">Preview</a>
                        <a href="#workflow">Workflow</a>
                    </nav>
                    <div class="landing-nav__actions">
                        <a href="{{ $portfolioUrl }}" target="_blank" rel="noopener" class="landing-nav__portfolio">Portfolio</a>
                        <a href="{{ route('login') }}" class="landing-btn landing-btn--primary">Log in</a>
                    </div>
                </div>
            </header>

            <main>
                <section class="landing-container landing-hero">
                    <p class="landing-eyebrow">Laravel outreach CRM · portfolio case study</p>

                    <h1 class="landing-hero__title">
                        B2B outreach with AI leads, tracked email, and sequences on schedule.
                    </h1>

                    <p class="landing-hero__lead">
                        Built to run live outbound for
                        <a href="{{ $fieldlineUrl }}" target="_blank" rel="noopener">FieldLine</a>.
                        Find UK leads, send cold email with open tracking, automate follow-ups on business days, and read the pipeline in one calm workspace.
                    </p>

                    <div class="landing-hero__actions">
                        <a href="{{ route('login') }}" class="landing-btn landing-btn--primary landing-btn--lg">Log in to live app</a>
                        <a href="{{ $contactHref }}" class="landing-btn landing-btn--secondary landing-btn--lg">Enquire about delivery</a>
                    </div>

                    <div class="landing-pills">
                        @foreach (['AI lead search', 'Email sequences', 'Open tracking', 'Live reports'] as $pill)
                            <span class="landing-pill">{{ $pill }}</span>
                        @endforeach
                    </div>
                </section>

                <section class="landing-container landing-container--narrow landing-preview-wrap" id="preview">
                    <div class="landing-preview">
                        <div class="landing-preview__header">
                            <div class="landing-preview__brand">
                                <div class="landing-preview__icon">ST</div>
                                <div>
                                    <p class="landing-preview__brand-title">Sales Tracker</p>
                                    <p class="landing-preview__brand-sub">Reports overview</p>
                                </div>
                            </div>
                            <div class="landing-preview__tabs">
                                <span class="is-active">Reports</span>
                                <span>Sequences</span>
                                <span>Inbox</span>
                            </div>
                        </div>

                        <div class="landing-stats">
                            @foreach ([['Sent', '253', ''], ['Open rate', '56%', 'landing-stat__value--green'], ['Reply rate', '4.6%', 'landing-stat__value--sky'], ['Active sequences', '22', '']] as [$label, $val, $colorClass])
                                <div class="landing-stat">
                                    <p class="landing-stat__label">{{ $label }}</p>
                                    <p class="landing-stat__value {{ $colorClass }}">{{ $val }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="landing-preview__grid">
                            <div class="landing-preview__panel">
                                <p class="landing-preview__panel-title">Sequences due today</p>
                                <p class="landing-preview__panel-sub">3 contacts on the automation queue</p>
                                <ul class="landing-preview__list">
                                    <li class="landing-preview__row">
                                        <span>Follow-up</span>
                                        <span class="landing-badge landing-badge--amber">Due now</span>
                                    </li>
                                    <li class="landing-preview__row">
                                        <span>Final nudge</span>
                                        <span class="landing-badge landing-badge--sky">Monday</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="landing-preview__panel">
                                <p class="landing-preview__panel-title">Hottest opens</p>
                                <p class="landing-preview__panel-sub">Prioritize personal follow-up</p>
                                <ul class="landing-preview__list">
                                    <li class="landing-preview__row landing-preview__row--plain">
                                        <div class="landing-preview__row-text">
                                            <p class="landing-preview__name">Sarah Mitchell</p>
                                            <p class="landing-preview__company">Northgate Security</p>
                                        </div>
                                        <span class="landing-badge landing-badge--green">14</span>
                                    </li>
                                    <li class="landing-preview__row landing-preview__row--plain">
                                        <div class="landing-preview__row-text">
                                            <p class="landing-preview__name">James Cooper</p>
                                            <p class="landing-preview__company">Summit Guarding</p>
                                        </div>
                                        <span class="landing-badge landing-badge--green">9</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <p class="landing-caption">Illustrative UI — log in to see your live data.</p>
                </section>

                <section class="landing-container landing-section" id="features">
                    <div class="landing-section__head">
                        <p class="landing-eyebrow">Features</p>
                        <h2 class="landing-section__title">Everything in one outreach workspace</h2>
                        <p class="landing-section__lead">Real modules from the shipped app — not mockups on a slide deck.</p>
                    </div>
                    <div class="landing-cards">
                        @foreach ($featureCards as $card)
                            <article class="landing-card">
                                <h3 class="landing-card__title">{{ $card['title'] }}</h3>
                                <p class="landing-card__copy">{{ $card['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="landing-container landing-section" id="workflow" style="padding-top: 0;">
                    <div class="landing-card landing-workflow">
                        <div class="landing-workflow__grid">
                            <div>
                                <p class="landing-eyebrow">Workflow</p>
                                <h2 class="landing-section__title">Search → email → automate → convert</h2>
                                <p class="landing-section__lead">Small batches of clean leads, sequenced email, then personal follow-up on contacts who engage.</p>
                                <a href="{{ route('login') }}" class="landing-btn landing-btn--primary" style="margin-top: 1.5rem;">Open the CRM</a>
                            </div>
                            <ol class="landing-steps">
                                @foreach ($workflowSteps as $i => $step)
                                    <li class="landing-step">
                                        <span class="landing-step__num">{{ $i + 1 }}</span>
                                        <div>
                                            <p class="landing-step__title">{{ $step['title'] }}</p>
                                            <p class="landing-step__copy">{{ $step['copy'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </section>

                <section class="landing-container landing-split" id="stack">
                    <div class="landing-card">
                        <p class="landing-eyebrow">Built with</p>
                        <h2 class="landing-card__title">Production Laravel stack</h2>
                        <div class="landing-tag-row">
                            @foreach ($techStack as $item)
                                <span class="landing-tag">{{ $item }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="landing-card">
                        <p class="landing-eyebrow">Case study</p>
                        <h2 class="landing-card__title">Selling FieldLine to UK guarding firms</h2>
                        <p class="landing-card__copy">Sequence discipline, hot-open surfacing, and honest pipeline status — so volume does not hide weak conversion.</p>
                        <div class="landing-tag-row">
                            <a href="{{ $fieldlineUrl }}" target="_blank" rel="noopener" class="landing-btn landing-btn--secondary">FieldLine demo</a>
                            <a href="{{ $portfolioUrl }}" target="_blank" rel="noopener" class="landing-btn landing-btn--secondary">yousiffarra.com</a>
                        </div>
                    </div>
                </section>

                <section class="landing-container" style="padding-bottom: 5rem;">
                    <div class="landing-cta">
                        <h2 class="landing-section__title">See it running on real outreach</h2>
                        <p class="landing-section__lead" style="max-width: 28rem; margin-left: auto; margin-right: auto;">Log in to explore reports, sequences, and inbox — or ask about a custom build.</p>
                        <div class="landing-cta__actions">
                            <a href="{{ route('login') }}" class="landing-btn landing-btn--primary landing-btn--lg">Log in</a>
                            <a href="{{ $contactHref }}" class="landing-btn landing-btn--secondary landing-btn--lg">Contact</a>
                        </div>
                    </div>
                    <footer class="landing-footer">
                        <p>&copy; {{ date('Y') }} Sales Tracker · <a href="{{ $portfolioUrl }}">Yousif Elfarra</a></p>
                        <a href="{{ route('login') }}">Log in</a>
                    </footer>
                </section>
            </main>
        </div>
    </body>
</html>
