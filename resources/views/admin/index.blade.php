<x-layouts.app :title="'Admin Overview'">
    @include('admin.partials.nav', [
        'title' => 'Overview',
        'subtitle' => 'High-level visibility across funding, payouts, and execution.',
        'stats' => $stats,
    ])

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="glass-panel">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Latest Deposits</h2>
                <a href="{{ route('admin.deposits.index') }}" class="pill-link">Open Page</a>
            </div>
            <div class="space-y-2 text-sm">
                @forelse($latestDeposits as $deposit)
                    <div class="card-muted">
                        <p class="font-medium text-slate-100">{{ $deposit->user->email }}</p>
                        <p class="text-slate-300">{{ number_format((float) ($deposit->local_amount ?? $deposit->amount), 2) }} {{ $deposit->local_currency ?? 'GHS' }} to {{ $deposit->currency }}</p>
                        <p class="text-xs text-slate-400">Wallet credit {{ number_format((float) $deposit->net_amount, 8) }} {{ $deposit->currency }}</p>
                        <p class="text-xs text-slate-400">#{{ $deposit->id }} | {{ $deposit->status }} | {{ $deposit->created_at }}</p>
                    </div>
                @empty
                    <p class="text-slate-400">No recent deposits.</p>
                @endforelse
            </div>
        </section>

        <section class="glass-panel">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Latest Withdrawals</h2>
                <a href="{{ route('admin.withdrawals.index') }}" class="pill-link">Open Page</a>
            </div>
            <div class="space-y-2 text-sm">
                @forelse($latestWithdrawals as $withdrawal)
                    <div class="card-muted">
                        <p class="font-medium text-slate-100">{{ $withdrawal->user->email }}</p>
                        <p class="text-slate-300">{{ number_format((float) ($withdrawal->local_amount ?? $withdrawal->amount), 2) }} {{ $withdrawal->local_currency ?? 'GHS' }} from {{ $withdrawal->currency }}</p>
                        <p class="text-xs text-slate-400">Wallet hold {{ number_format((float) $withdrawal->amount, 8) }} {{ $withdrawal->currency }}</p>
                        <p class="text-xs text-slate-400">#{{ $withdrawal->id }} | {{ $withdrawal->status }} | {{ $withdrawal->created_at }}</p>
                    </div>
                @empty
                    <p class="text-slate-400">No recent withdrawals.</p>
                @endforelse
            </div>
        </section>

        <section class="glass-panel">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Latest Trades</h2>
                <a href="{{ route('admin.trades.index') }}" class="pill-link">Open Page</a>
            </div>
            <div class="space-y-2 text-sm">
                @forelse($latestTrades as $trade)
                    <div class="card-muted">
                        <p class="font-medium text-slate-100">{{ $trade->user->email }}</p>
                        <p class="text-slate-300">{{ $trade->symbol }} | {{ $trade->direction }} | {{ number_format((float) $trade->amount, 8) }} {{ optional($trade->wallet)->currency ?? 'USDT' }}</p>
                        <p class="text-xs text-slate-400">#{{ $trade->id }} | {{ $trade->status }} | {{ $trade->created_at }}</p>
                    </div>
                @empty
                    <p class="text-slate-400">No recent trades.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
