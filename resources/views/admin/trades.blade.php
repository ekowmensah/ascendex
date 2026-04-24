<x-layouts.app :title="'Admin Trades'">
    @include('admin.partials.nav', [
        'title' => 'Trades',
        'subtitle' => 'Audit live and settled contracts with symbol and outcome filters.',
        'stats' => $stats,
    ])

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Trade Ledger</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $trades->total() }} trades</span>
        </div>

        <form method="GET" action="{{ route('admin.trades.index') }}" class="mb-3 grid gap-2 md:grid-cols-2 xl:grid-cols-[1.2fr,0.6fr,0.6fr,0.6fr,0.7fr,0.7fr,auto,auto]">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search trade ID, user, symbol" class="input-dark">
            <select name="status" class="input-dark">
                <option value="">All statuses</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="symbol" class="input-dark">
                <option value="">All symbols</option>
                @foreach($symbolOptions as $symbol)
                    <option value="{{ $symbol }}" @selected($filters['symbol'] === $symbol)>{{ $symbol }}</option>
                @endforeach
            </select>
            <select name="direction" class="input-dark">
                <option value="">All directions</option>
                @foreach($directionOptions as $direction)
                    <option value="{{ $direction }}" @selected($filters['direction'] === $direction)>{{ $direction }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="input-dark">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="input-dark">
            <button class="btn-primary" type="submit">Filter Trades</button>
            <a href="{{ route('admin.trades.index') }}" class="pill-link inline-flex items-center justify-center">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="table-dark min-w-[58rem]">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Asset</th>
                    <th>Direction</th>
                    <th>Amount</th>
                    <th>Entry</th>
                    <th>Close</th>
                    <th>Status</th>
                    <th>Placed</th>
                </tr>
                </thead>
                <tbody>
                @forelse($trades as $trade)
                    @php
                        $statusClass = match ($trade->status) {
                            'WIN' => 'border-emerald-400/40 bg-emerald-500/10 text-emerald-200',
                            'LOSE' => 'border-rose-400/40 bg-rose-500/10 text-rose-200',
                            default => 'border-amber-400/40 bg-amber-500/10 text-amber-200',
                        };
                    @endphp
                    <tr>
                        <td>#{{ $trade->id }}</td>
                        <td>
                            <p class="text-slate-100">{{ $trade->user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $trade->user->email }}</p>
                        </td>
                        <td>{{ $trade->symbol }}</td>
                        <td>{{ $trade->direction }}</td>
                        <td>{{ number_format((float) $trade->amount, 2) }}</td>
                        <td>{{ number_format((float) $trade->entry_price, 2) }}</td>
                        <td>{{ $trade->close_price ? number_format((float) $trade->close_price, 2) : '-' }}</td>
                        <td><span class="rounded-full border px-2 py-0.5 text-[11px] {{ $statusClass }}">{{ $trade->status }}</span></td>
                        <td class="text-xs text-slate-400">{{ $trade->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-slate-400">No trades found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $trades->links() }}</div>
    </section>
</x-layouts.app>
