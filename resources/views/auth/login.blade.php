<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sign in | Sales Tracker</title>
        <x-assets />
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100">
        <div class="flex min-h-screen items-center justify-center px-4">
            <div class="w-full max-w-md panel">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-300">Sales Tracker</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Sign in</h1>
                <p class="mt-2 text-sm text-slate-400">Use your team account to access the outreach CRM.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div>
                        <label class="label" for="password">Password</label>
                        <input class="input" id="password" type="password" name="password" required>
                    </div>
                    <label class="inline-flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Remember me
                    </label>
                    <button class="btn-primary w-full" type="submit">Sign in</button>
                </form>
            </div>
        </div>
    </body>
</html>
