@php
    use App\Support\Permissions;

    $navItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'permission' => Permissions::DASHBOARD_VIEW, 'icon' => 'dashboard'],
        ['route' => 'contacts.index', 'label' => 'Contacts', 'permission' => Permissions::CONTACTS_VIEW, 'icon' => 'contacts'],
        ['route' => 'campaigns.index', 'label' => 'Campaigns', 'permission' => Permissions::CAMPAIGNS_VIEW, 'icon' => 'campaigns'],
        ['route' => 'interactions.index', 'label' => 'Interactions', 'permission' => Permissions::INTERACTIONS_VIEW, 'icon' => 'interactions'],
        ['route' => 'follow-ups.index', 'label' => 'Follow-ups', 'permission' => Permissions::FOLLOW_UPS_VIEW, 'icon' => 'followups'],
        ['route' => 'email-threads.index', 'label' => 'Inbox', 'permission' => Permissions::EMAILS_INBOX, 'icon' => 'inbox'],
        ['route' => 'lead-searches.index', 'label' => 'AI Lead Search', 'permission' => Permissions::LEAD_SEARCHES_VIEW, 'icon' => 'leadsearch'],
        ['route' => 'lead-search-presets.index', 'label' => 'AI Prompts', 'permission' => Permissions::LEAD_SEARCH_PRESETS_VIEW, 'icon' => 'prompts'],
        ['route' => 'email-templates.index', 'label' => 'Email Templates', 'permission' => Permissions::EMAIL_TEMPLATES_VIEW, 'icon' => 'templates'],
        ['route' => 'reports.index', 'label' => 'Reports', 'permission' => Permissions::REPORTS_VIEW, 'icon' => 'reports'],
        ['route' => 'users.index', 'label' => 'Users', 'permission' => Permissions::USERS_VIEW, 'icon' => 'users'],
        ['route' => 'roles.index', 'label' => 'Roles & Permissions', 'permission' => Permissions::ROLES_VIEW, 'icon' => 'roles'],
    ];

    $renderNavIcon = static function (string $icon): string {
        return match ($icon) {
            'dashboard' => '<path d="M3 4.75A1.75 1.75 0 0 1 4.75 3h2.5A1.75 1.75 0 0 1 9 4.75v2.5A1.75 1.75 0 0 1 7.25 9h-2.5A1.75 1.75 0 0 1 3 7.25zm0 7A1.75 1.75 0 0 1 4.75 10h2.5A1.75 1.75 0 0 1 9 11.75v2.5A1.75 1.75 0 0 1 7.25 16h-2.5A1.75 1.75 0 0 1 3 14.25zM10 4.75A1.75 1.75 0 0 1 11.75 3h2.5A1.75 1.75 0 0 1 16 4.75v2.5A1.75 1.75 0 0 1 14.25 9h-2.5A1.75 1.75 0 0 1 10 7.25zm0 7A1.75 1.75 0 0 1 11.75 10h2.5A1.75 1.75 0 0 1 16 11.75v2.5A1.75 1.75 0 0 1 14.25 16h-2.5A1.75 1.75 0 0 1 10 14.25z"/>',
            'contacts' => '<path d="M8 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3m0 1.5c-2.67 0-5 1.3-5 3V14h10v-1.5c0-1.7-2.33-3-5-3m5.75-1.25a2.25 2.25 0 1 0 0-4.5m.5 5.05a4.97 4.97 0 0 1 2.75 1.7V14H14.5v-.7c0-.35-.09-.69-.25-1"/>',
            'campaigns' => '<path d="M3 6.5 8 4l5 2.5v5L8 14l-5-2.5zm5-4v11.5m5-7.5L8 9 3 6.5"/>',
            'interactions' => '<path d="M4.75 3h6.5A1.75 1.75 0 0 1 13 4.75v6.5A1.75 1.75 0 0 1 11.25 13h-2.5l-2.5 2v-2h-1.5A1.75 1.75 0 0 1 3 11.25v-6.5A1.75 1.75 0 0 1 4.75 3M5.5 6.25h5m-5 2.5h3.5"/>',
            'followups' => '<path d="M8 3.5a4.5 4.5 0 1 0 4.5 4.5M8 5.75V8l1.75 1.75M8 1.75v1.5m0 9v1.5m6.25-6.25h-1.5m-9 0h-1.5"/>',
            'inbox' => '<path d="M3 5.75A1.75 1.75 0 0 1 4.75 4h6.5A1.75 1.75 0 0 1 13 5.75v4.5A1.75 1.75 0 0 1 11.25 12h-1.9a1.5 1.5 0 0 1-2.7 0h-1.9A1.75 1.75 0 0 1 3 10.25zm1.75-.25 3.25 2.44L11.25 5.5"/>',
            'leadsearch' => '<path d="M7.25 3.5a3.75 3.75 0 1 0 2.33 6.69l2.11 2.12 1.06-1.06-2.12-2.11A3.75 3.75 0 0 0 7.25 3.5m0 1.5a2.25 2.25 0 1 1 0 4.5 2.25 2.25 0 0 1 0-4.5"/>',
            'prompts' => '<path d="M8 3 3.75 5.25v3.5C3.75 11.49 5.56 14 8 15c2.44-1 4.25-3.51 4.25-6.25v-3.5zm-.75 3h1.5v2h2v1.5h-2v2h-1.5v-2h-2V8h2z"/>',
            'templates' => '<path d="M4.75 3h6.5A1.75 1.75 0 0 1 13 4.75v6.5A1.75 1.75 0 0 1 11.25 13h-6.5A1.75 1.75 0 0 1 3 11.25v-6.5A1.75 1.75 0 0 1 4.75 3m1 2.25h4.5m-4.5 2.5h4.5m-4.5 2.5H9"/>',
            'reports' => '<path d="M4.75 3h6.5A1.75 1.75 0 0 1 13 4.75v6.5A1.75 1.75 0 0 1 11.25 13h-6.5A1.75 1.75 0 0 1 3 11.25v-6.5A1.75 1.75 0 0 1 4.75 3M5.5 10h1.25V7.75H5.5zm2.38 0h1.24V6.5H7.88zm2.37 0h1.25V5h-1.25z"/>',
            'users' => '<path d="M8 8a2.75 2.75 0 1 0-2.75-2.75A2.75 2.75 0 0 0 8 8m0 1.5c-2.73 0-4.95 1.43-4.95 3.2V14h9.9v-1.3c0-1.77-2.22-3.2-4.95-3.2"/>',
            'roles' => '<path d="M8 3.25 4 5.1v2.77c0 2.08 1.45 4.56 4 5.38 2.55-.82 4-3.3 4-5.38V5.1zm0 3a1.25 1.25 0 1 1-1.25 1.25A1.25 1.25 0 0 1 8 6.25m0 5.15a3.9 3.9 0 0 1-2.02-.56c.09-.89.95-1.59 2.02-1.59s1.93.7 2.02 1.59A3.9 3.9 0 0 1 8 11.4"/>',
            default => '<circle cx="8" cy="8" r="3"/>',
        };
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Sales Tracker CRM' }}</title>
        <x-assets />
    </head>
    <body class="min-h-screen overflow-x-hidden bg-slate-950 text-slate-100">
        <div class="flex min-h-screen" data-mobile-shell>
            <div class="fixed inset-0 z-40 hidden bg-slate-950/70 backdrop-blur-sm lg:hidden" data-mobile-nav-overlay></div>

            <aside id="mobile-navigation" class="fixed inset-y-0 left-0 z-50 flex w-[min(18rem,88vw)] -translate-x-full flex-col overflow-y-auto border-r border-slate-800 bg-slate-900 p-5 transition-transform duration-200 sm:p-6 lg:hidden" data-mobile-nav>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-300">Sales Tracker</p>
                        <h1 class="text-2xl font-semibold text-white">Outreach CRM</h1>
                        <p class="text-sm text-slate-400">Multi-user CRM with roles and configurable permissions.</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-semibold text-slate-200"
                        data-mobile-nav-close
                        aria-label="Close navigation"
                    >
                        Close
                    </button>
                </div>

                <nav class="mt-8 space-y-2 text-sm">
                    @foreach ($navItems as $item)
                        @can($item['permission'])
                            @php
                                $prefix = explode('.', $item['route'])[0];
                                $isActive = request()->routeIs($item['route']) || str_starts_with((string) request()->route()?->getName(), $prefix.'.');
                            @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                data-mobile-nav-link
                                @class([
                                    'flex items-center gap-3 rounded-xl px-4 py-3 transition',
                                    'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30' => $isActive,
                                    'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive,
                                ])
                            >
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-800/90">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $renderNavIcon($item['icon']) !!}
                                    </svg>
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endcan
                    @endforeach
                </nav>

                <div class="mt-8 border-t border-slate-800 pt-6">
                    <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400">{{ auth()->user()->roles->pluck('name')->implode(', ') ?: 'No role' }}</p>
                    <form method="post" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button class="btn-secondary w-full" type="submit">Sign out</button>
                    </form>
                </div>
            </aside>

            <div class="fixed inset-y-0 left-0 z-30 hidden lg:block" data-sidebar-rail>
                <aside
                    class="flex h-full w-72 flex-col overflow-hidden border-r border-slate-800 bg-slate-950 p-6 transition-[width] duration-200"
                    data-desktop-sidebar
                >
                    <div class="sidebar-header flex items-start justify-between gap-3">
                        <div class="sidebar-brand min-w-0 space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-300">Sales Tracker</p>
                            <h1 class="sidebar-brand-title text-2xl font-semibold text-white">Outreach CRM</h1>
                            <p class="sidebar-brand-copy text-sm text-slate-400">Multi-user CRM with roles and configurable permissions.</p>
                        </div>
                        <button
                            type="button"
                            class="sidebar-toggle inline-flex shrink-0 rounded-full border border-slate-700 bg-slate-950 p-2 text-slate-300 shadow-lg shadow-slate-950/60 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white"
                            data-sidebar-toggle
                            aria-expanded="true"
                            aria-label="Collapse sidebar"
                            title="Collapse sidebar"
                        >
                            <svg class="sidebar-toggle-icon h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M10 3.5 5.5 8 10 12.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <nav class="mt-8 min-h-0 flex-1 space-y-2 overflow-x-hidden overflow-y-auto pr-1 text-sm">
                        @foreach ($navItems as $item)
                            @can($item['permission'])
                                @php
                                    $prefix = explode('.', $item['route'])[0];
                                    $isActive = request()->routeIs($item['route']) || str_starts_with((string) request()->route()?->getName(), $prefix.'.');
                                @endphp
                                <a
                                    href="{{ route($item['route']) }}"
                                    title="{{ $item['label'] }}"
                                    @class([
                                        'sidebar-nav-link flex items-center gap-3 rounded-xl px-4 py-3 transition',
                                        'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30' => $isActive,
                                        'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive,
                                    ])
                                >
                                    <span class="sidebar-nav-initial flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-800/90">
                                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            {!! $renderNavIcon($item['icon']) !!}
                                        </svg>
                                    </span>
                                    <span class="sidebar-nav-label truncate">{{ $item['label'] }}</span>
                                </a>
                            @endcan
                        @endforeach
                    </nav>

                    <div class="sidebar-user mt-auto shrink-0 border-t border-slate-800 pt-6">
                        <p class="sidebar-user-name text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="sidebar-user-role text-xs text-slate-400">{{ auth()->user()->roles->pluck('name')->implode(', ') ?: 'No role' }}</p>
                        <form method="post" action="{{ route('logout') }}" class="sidebar-logout mt-4">
                            @csrf
                            <button class="btn-secondary w-full" type="submit">Sign out</button>
                        </form>
                    </div>
                </aside>
            </div>

            <main class="min-w-0 flex-1 lg:ml-72" data-desktop-main>
                <header class="border-b border-slate-800 bg-slate-950/80 px-3 py-4 backdrop-blur sm:px-6 lg:px-10">
                    <div class="mb-4 flex items-center justify-between gap-3 lg:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-slate-600 hover:bg-slate-800"
                            data-mobile-nav-open
                            aria-expanded="false"
                            aria-controls="mobile-navigation"
                        >
                            Menu
                        </button>
                        <div class="min-w-0 text-right">
                            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ auth()->user()->roles->pluck('name')->implode(', ') ?: 'No role' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-400">{{ $eyebrow ?? 'Internal CRM' }}</p>
                            <h2 class="break-words text-xl font-semibold text-white sm:text-2xl">{{ $heading ?? 'Dashboard' }}</h2>
                        </div>

                        <div class="page-actions">
                            @can(Permissions::CONTACTS_CREATE)
                                <a href="{{ route('contacts.create') }}" class="btn-secondary">New Contact</a>
                            @endcan
                            @can(Permissions::INTERACTIONS_CREATE)
                                <a href="{{ route('interactions.create') }}" class="btn-secondary">Log Interaction</a>
                            @endcan
                            @can(Permissions::LEAD_SEARCHES_CREATE)
                                <a href="{{ route('lead-searches.create') }}" class="btn-primary">Run AI Lead Search</a>
                            @endcan
                        </div>
                    </div>
                </header>

                <div class="min-w-0 max-w-full px-3 py-4 sm:px-6 sm:py-6 lg:px-10">
                    @if (session('status'))
                        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                            <p class="font-semibold">Please fix the highlighted fields.</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
        <script>
            (function () {
                var syncMobileMainLayout = function () {
                    var desktopMain = document.querySelector('[data-desktop-main]');
                    if (!desktopMain) return;

                    if (window.innerWidth < 1024) {
                        desktopMain.style.marginLeft = '0';
                        desktopMain.style.width = '100%';
                        desktopMain.style.maxWidth = '100vw';
                    } else {
                        desktopMain.style.width = '';
                        desktopMain.style.maxWidth = '';
                    }
                };

                syncMobileMainLayout();
                window.addEventListener('load', syncMobileMainLayout);
                window.addEventListener('resize', syncMobileMainLayout);
            })();
        </script>
    </body>
</html>
