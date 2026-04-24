@php
    $navItems = [
        ['route' => 'admin.index', 'label' => 'Overview'],
        ['route' => 'admin.users.index', 'label' => 'Users'],
        ['route' => 'admin.deposits.index', 'label' => 'Deposits'],
        ['route' => 'admin.withdrawals.index', 'label' => 'Withdrawals'],
        ['route' => 'admin.trades.index', 'label' => 'Trades'],
        ['route' => 'admin.settings.index', 'label' => 'Settings'],
    ];
@endphp

<section class="glass-panel mb-4">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Admin Console</p>
            <h1 class="text-2xl font-semibold">{{ $title ?? 'Control Center' }}</h1>
            @if(!empty($subtitle))
                <p class="mt-1 text-sm text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="rounded-xl border border-slate-700/80 bg-slate-950/70 px-3 py-2 text-right">
            <p class="text-xs uppercase tracking-wide text-slate-400">As Of</p>
            <p class="text-sm font-medium text-slate-100">{{ now()->format('M d, Y H:i') }}</p>
        </div>
    </div>

    <nav class="mb-3 grid gap-2 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($navItems as $item)
            @php
                $isActive = request()->routeIs($item['route']);
                $linkClass = $isActive
                    ? 'border-cyan-400/50 bg-cyan-500/15 text-cyan-100'
                    : 'border-slate-700/80 bg-slate-950/70 text-slate-300 hover:border-cyan-500/40 hover:text-cyan-200';
            @endphp
            <a href="{{ route($item['route']) }}" class="rounded-xl border px-3 py-2 text-center text-sm font-medium transition {{ $linkClass }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card-muted">
            <p class="text-xs uppercase tracking-wide text-slate-400">Pending Deposits</p>
            <p class="mt-1 text-xl font-semibold text-amber-300">{{ $stats['pending_deposits'] }}</p>
        </div>
        <div class="card-muted">
            <p class="text-xs uppercase tracking-wide text-slate-400">Pending Withdrawals</p>
            <p class="mt-1 text-xl font-semibold text-amber-300">{{ $stats['pending_withdrawals'] }}</p>
        </div>
        <div class="card-muted">
            <p class="text-xs uppercase tracking-wide text-slate-400">Deposits Today (GHS)</p>
            <p class="mt-1 text-xl font-semibold text-emerald-300">{{ number_format((float) $stats['approved_deposits_today'], 2) }}</p>
        </div>
        <div class="card-muted">
            <p class="text-xs uppercase tracking-wide text-slate-400">Withdrawals Today (GHS)</p>
            <p class="mt-1 text-xl font-semibold text-rose-300">{{ number_format((float) $stats['approved_withdrawals_today'], 2) }}</p>
        </div>
    </div>
</section>
