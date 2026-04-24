<x-layouts.app :title="'Wallet'">
    <section class="glass-panel mb-4">
        <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Treasury</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Wallet & Ledger</h1>
                <p class="mt-1 text-sm text-slate-400">View each asset wallet, its locked funds, and the full transaction trail.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('deposit.index') }}" class="btn-primary text-sm">Deposit</a>
                <a href="{{ route('withdrawal.index') }}" class="pill-link text-sm">Withdraw</a>
            </div>
        </div>
    </section>

    <div class="mb-4 grid gap-3 md:grid-cols-3">
        @forelse($wallets as $wallet)
            <div class="glass-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-400">Wallet</p>
                        <p class="mt-1 text-lg font-semibold text-cyan-300">{{ $wallet->currency }}</p>
                        <p class="text-xs text-slate-500">{{ $wallet->currency === 'USDT' ? 'Stable trading balance' : 'Asset wallet balance' }}</p>
                    </div>
                    <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">
                        {{ number_format((float) $wallet->bonus, 2) }} bonus
                    </span>
                </div>
                <div class="mt-4 grid gap-2 text-sm">
                    <div class="card-muted flex items-center justify-between">
                        <span class="text-slate-300">Available</span>
                        <span class="font-semibold text-slate-100">{{ number_format((float) $wallet->balance, 8) }} {{ $wallet->currency }}</span>
                    </div>
                    <div class="card-muted flex items-center justify-between">
                        <span class="text-slate-300">Locked</span>
                        <span class="font-semibold text-amber-200">{{ number_format((float) $wallet->locked_balance, 8) }} {{ $wallet->currency }}</span>
                    </div>
                    <div class="card-muted flex items-center justify-between">
                        <span class="text-slate-300">Bonus</span>
                        <span class="font-semibold text-cyan-200">{{ number_format((float) $wallet->bonus, 8) }} {{ $wallet->currency }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-panel md:col-span-3">
                <p class="text-slate-400">No wallets available yet.</p>
            </div>
        @endforelse
    </div>

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Transaction History</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $transactions->total() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dark min-w-[52rem]">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Currency</th>
                    <th>Amount</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>Reference</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @forelse($transactions as $txn)
                    <tr>
                        <td class="uppercase text-xs tracking-wide">{{ str_replace('_', ' ', $txn->type) }}</td>
                        <td class="font-medium text-slate-300">{{ optional($txn->wallet)->currency ?? data_get($txn->meta, 'currency', 'USDT') }}</td>
                        <td class="font-medium {{ (float) $txn->amount >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ number_format((float) $txn->amount, 8) }}
                        </td>
                        <td>{{ number_format((float) $txn->balance_before, 8) }}</td>
                        <td>{{ number_format((float) $txn->balance_after, 8) }}</td>
                        <td class="text-xs text-slate-400">{{ $txn->reference ?? '-' }}</td>
                        <td class="text-xs text-slate-400">{{ $txn->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-400">No transactions yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $transactions->links() }}</div>
    </section>
</x-layouts.app>
