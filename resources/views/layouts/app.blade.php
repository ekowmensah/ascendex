<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <title>{{ $title ?? 'TransMarket' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
    <header class="mb-6 rounded-2xl border border-slate-800 bg-slate-900/80 p-4 backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-wide">Trans Market Group</a>
            @auth
                <nav class="flex flex-wrap items-center gap-2 text-sm">
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('trade.index') }}">Trade</a>
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('wallet.index') }}">Wallet</a>
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('deposit.index') }}">Deposits</a>
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('withdrawal.index') }}">Withdrawals</a>
                    @if(auth()->user()->isAdmin())
                        <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('admin.index') }}">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout.perform') }}">
                        @csrf
                        <button class="rounded-lg bg-slate-800 px-3 py-1.5 hover:bg-slate-700" type="submit">Logout</button>
                    </form>
                </nav>
            @else
                <nav class="flex items-center gap-2 text-sm">
                    <a class="rounded-lg px-3 py-1.5 hover:bg-slate-800" href="{{ route('login') }}">Login</a>
                    <a class="rounded-lg bg-cyan-600 px-3 py-1.5 hover:bg-cyan-500" href="{{ route('register.form') }}">Register</a>
                </nav>
            @endauth
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
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

    <main>
        {{ $slot }}
    </main>
</div>
</body>
</html>
