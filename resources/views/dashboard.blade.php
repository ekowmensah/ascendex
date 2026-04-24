<x-layouts.app :title="'Dashboard'">
    <section class="glass-panel mb-4">
        <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Market Overview</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Crypto Binary Dashboard</h1>
                <p class="mt-1 text-sm text-slate-400">Monitor reference prices and jump directly into your next action.</p>
            </div>
            <a href="{{ route('trade.index') }}" class="btn-primary">Open Trade Desk</a>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-3">
            <a href="{{ route('wallet.index') }}" class="card-muted text-sm text-slate-200 hover:border-cyan-400/40">Wallet</a>
            <a href="{{ route('deposit.index') }}" class="card-muted text-sm text-slate-200 hover:border-cyan-400/40">Deposit Funds</a>
            <a href="{{ route('withdrawal.index') }}" class="card-muted text-sm text-slate-200 hover:border-cyan-400/40">Withdraw</a>
        </div>
    </section>

    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-base font-semibold">Live Reference Market</h2>
        <span class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-0.5 text-[11px] text-emerald-200">Live</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="glass-panel">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-400">BTCUSDT</p>
                    <p class="mt-2 text-3xl font-bold text-cyan-300">{{ number_format((float) $btcPrice, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-400">Bitcoin spot market reference</p>
                </div>
                <span class="rounded-full border border-cyan-400/40 bg-cyan-500/10 px-2 py-0.5 text-[11px] text-cyan-200">Primary</span>
            </div>
        </div>
        <div class="glass-panel">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-400">ETHUSDT</p>
                    <p class="mt-2 text-3xl font-bold text-violet-300">{{ number_format((float) $ethPrice, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-400">Ethereum spot market reference</p>
                </div>
                <span class="rounded-full border border-violet-400/40 bg-violet-500/10 px-2 py-0.5 text-[11px] text-violet-200">Secondary</span>
            </div>
        </div>
    </div>
</x-layouts.app>
