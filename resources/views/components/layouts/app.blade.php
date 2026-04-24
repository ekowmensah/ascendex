<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>{{ $title ?? 'Trans Market Group' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-100">
<div class="app-shell">
    <header class="glass-panel mb-4 overflow-hidden md:mb-6">
        <div class="mb-3 flex items-center justify-between md:mb-4">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
                <span class="rounded-lg bg-cyan-400/20 px-2 py-1 text-[11px] font-bold tracking-[0.2em] text-cyan-200">AEX</span>
                <span class="text-base font-semibold tracking-wide sm:text-lg">Trans Market Group</span>
            </a>
            <span class="hidden rounded-full border border-slate-700/80 bg-slate-950/70 px-3 py-1 text-xs text-slate-300 sm:inline-flex">Crypto Options Desk</span>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            @auth
                <nav class="hidden flex-wrap items-center gap-2 text-sm md:flex">
                    <a class="pill-link" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="pill-link" href="{{ route('trade.index') }}">Trade</a>
                    <a class="pill-link" href="{{ route('wallet.index') }}">Wallet</a>
                    <a class="pill-link" href="{{ route('deposit.index') }}">Deposits</a>
                    <a class="pill-link" href="{{ route('withdrawal.index') }}">Withdrawals</a>
                    @if(auth()->user()->isAdmin())
                        <a class="pill-link" href="{{ route('admin.index') }}">Admin</a>
                    @endif
                </nav>

                <div class="flex items-center gap-2">
                    <span class="max-w-[11rem] truncate rounded-full border border-slate-700/80 bg-slate-950/70 px-3 py-1 text-xs text-slate-300">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout.perform') }}">
                        @csrf
                        <button class="pill-link" type="submit">Logout</button>
                    </form>
                </div>
            @else
                <nav class="flex items-center gap-2 text-sm">
                    <a class="pill-link" href="{{ route('login') }}">Login</a>
                    <a class="btn-primary" href="{{ route('register.form') }}">Register</a>
                </nav>
            @endauth
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main class="pb-24 md:pb-0">
        {{ $slot }}
    </main>

    @auth
        <nav class="mobile-nav-dock md:hidden" aria-label="Mobile navigation">
            <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'mobile-nav-link-active' : '' }}" title="Dashboard">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-10.5Z"/></svg>
                <span>Home</span>
            </a>
            <a href="{{ route('wallet.index') }}" class="mobile-nav-link {{ request()->routeIs('wallet.*') ? 'mobile-nav-link-active' : '' }}" title="Wallet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2H5a2 2 0 0 0 0 4h14v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 9h2v4h-2a2 2 0 1 1 0-4Z"/></svg>
                <span>Wallet</span>
            </a>
            <a href="{{ route('trade.index') }}" class="mobile-nav-link mobile-nav-link-trade {{ request()->routeIs('trade.*') ? 'mobile-nav-link-active' : '' }}" title="Trade">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m4 14 4-4 4 3 6-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 6v5h-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18"/></svg>
                <span>Trade</span>
            </a>
            <a href="{{ route('deposit.index') }}" class="mobile-nav-link {{ request()->routeIs('deposit.*') ? 'mobile-nav-link-active' : '' }}" title="Deposit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12"/><path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/><rect x="3" y="18" width="18" height="3" rx="1"/></svg>
                <span>Deposit</span>
            </a>
            <a href="{{ route('withdrawal.index') }}" class="mobile-nav-link {{ request()->routeIs('withdrawal.*') ? 'mobile-nav-link-active' : '' }}" title="Withdrawal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9"/><path stroke-linecap="round" stroke-linejoin="round" d="m17 14-5-5-5 5"/><rect x="3" y="3" width="18" height="3" rx="1"/></svg>
                <span>Withdraw</span>
            </a>
        </nav>
    @endauth
</div>
</body>
</html>
