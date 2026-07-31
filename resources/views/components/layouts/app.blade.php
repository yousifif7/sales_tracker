@php
    use App\Support\Permissions;
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
                    @php
                        $navItems = [
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'permission' => Permissions::DASHBOARD_VIEW],
                            ['route' => 'contacts.index', 'label' => 'Contacts', 'permission' => Permissions::CONTACTS_VIEW],
                            ['route' => 'campaigns.index', 'label' => 'Campaigns', 'permission' => Permissions::CAMPAIGNS_VIEW],
                            ['route' => 'interactions.index', 'label' => 'Interactions', 'permission' => Permissions::INTERACTIONS_VIEW],
                            ['route' => 'follow-ups.index', 'label' => 'Follow-ups', 'permission' => Permissions::FOLLOW_UPS_VIEW],
                            ['route' => 'email-threads.index', 'label' => 'Inbox', 'permission' => Permissions::EMAILS_INBOX],
                            ['route' => 'lead-searches.index', 'label' => 'AI Lead Search', 'permission' => Permissions::LEAD_SEARCHES_VIEW],
                            ['route' => 'lead-search-presets.index', 'label' => 'AI Prompts', 'permission' => Permissions::LEAD_SEARCH_PRESETS_VIEW],
                            ['route' => 'email-templates.index', 'label' => 'Email Templates', 'permission' => Permissions::EMAIL_TEMPLATES_VIEW],
                            ['route' => 'reports.index', 'label' => 'Reports', 'permission' => Permissions::REPORTS_VIEW],
                            ['route' => 'users.index', 'label' => 'Users', 'permission' => Permissions::USERS_VIEW],
                            ['route' => 'roles.index', 'label' => 'Roles & Permissions', 'permission' => Permissions::ROLES_VIEW],
                        ];
                    @endphp

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
                                    'block rounded-xl px-4 py-3 transition',
                                    'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30' => $isActive,
                                    'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive,
                                ])
                            >
                                {{ $item['label'] }}
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

            <aside class="hidden w-72 shrink-0 border-r border-slate-800 bg-slate-900/80 p-6 lg:block">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-300">Sales Tracker</p>
                    <h1 class="text-2xl font-semibold text-white">Outreach CRM</h1>
                    <p class="text-sm text-slate-400">Multi-user CRM with roles and configurable permissions.</p>
                </div>

                <nav class="mt-8 space-y-2 text-sm">
                    @php
                        $navItems = [
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'permission' => Permissions::DASHBOARD_VIEW],
                            ['route' => 'contacts.index', 'label' => 'Contacts', 'permission' => Permissions::CONTACTS_VIEW],
                            ['route' => 'campaigns.index', 'label' => 'Campaigns', 'permission' => Permissions::CAMPAIGNS_VIEW],
                            ['route' => 'interactions.index', 'label' => 'Interactions', 'permission' => Permissions::INTERACTIONS_VIEW],
                            ['route' => 'follow-ups.index', 'label' => 'Follow-ups', 'permission' => Permissions::FOLLOW_UPS_VIEW],
                            ['route' => 'email-threads.index', 'label' => 'Inbox', 'permission' => Permissions::EMAILS_INBOX],
                            ['route' => 'lead-searches.index', 'label' => 'AI Lead Search', 'permission' => Permissions::LEAD_SEARCHES_VIEW],
                            ['route' => 'lead-search-presets.index', 'label' => 'AI Prompts', 'permission' => Permissions::LEAD_SEARCH_PRESETS_VIEW],
                            ['route' => 'email-templates.index', 'label' => 'Email Templates', 'permission' => Permissions::EMAIL_TEMPLATES_VIEW],
                            ['route' => 'reports.index', 'label' => 'Reports', 'permission' => Permissions::REPORTS_VIEW],
                            ['route' => 'users.index', 'label' => 'Users', 'permission' => Permissions::USERS_VIEW],
                            ['route' => 'roles.index', 'label' => 'Roles & Permissions', 'permission' => Permissions::ROLES_VIEW],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        @can($item['permission'])
                            @php
                                $prefix = explode('.', $item['route'])[0];
                                $isActive = request()->routeIs($item['route']) || str_starts_with((string) request()->route()?->getName(), $prefix.'.');
                            @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    'block rounded-xl px-4 py-3 transition',
                                    'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/30' => $isActive,
                                    'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive,
                                ])
                            >
                                {{ $item['label'] }}
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

            <main class="min-w-0 flex-1">
                <header class="border-b border-slate-800 bg-slate-950/80 px-4 py-4 backdrop-blur sm:px-6 lg:px-10">
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

                <div class="px-4 py-5 sm:px-6 sm:py-6 lg:px-10">
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
    </body>
</html>
