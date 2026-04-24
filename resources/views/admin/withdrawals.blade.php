<x-layouts.app :title="'Admin Withdrawals'">
    @include('admin.partials.nav', [
        'title' => 'Withdrawals',
        'subtitle' => 'Process payout requests with account-level destination details.',
        'stats' => $stats,
    ])

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Withdrawal Review Queue</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $withdrawals->total() }} requests</span>
        </div>

        <form method="GET" action="{{ route('admin.withdrawals.index') }}" class="mb-3 grid gap-2 md:grid-cols-2 xl:grid-cols-[1.2fr,0.6fr,0.6fr,0.7fr,0.7fr,auto,auto]">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search ID, user, account, phone" class="input-dark">
            <select name="status" class="input-dark">
                <option value="">All statuses</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="provider" class="input-dark">
                <option value="">All providers</option>
                @foreach($providerOptions as $providerOption)
                    <option value="{{ $providerOption }}" @selected($filters['provider'] === $providerOption)>{{ strtoupper($providerOption) }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="input-dark">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="input-dark">
            <button class="btn-primary" type="submit">Filter Withdrawals</button>
            <a href="{{ route('admin.withdrawals.index') }}" class="pill-link inline-flex items-center justify-center">Reset</a>
        </form>

        <div class="space-y-3">
            @forelse($withdrawals as $withdrawal)
                @php
                    $destinationParts = explode(':', (string) $withdrawal->destination);
                    $providerKey = strtolower(trim((string) ($withdrawal->mobile_provider ?: ($destinationParts[1] ?? 'mobile_money'))));
                    $provider = strtoupper(str_replace('_', ' ', $providerKey));
                    $accountNumber = $withdrawal->account_number ?: ($destinationParts[2] ?? '-');
                    $accountName = $withdrawal->account_name ?: '-';
                    $accountPhone = $withdrawal->account_phone ?: '-';

                    $statusClass = match ($withdrawal->status) {
                        'APPROVED' => 'border-emerald-400/40 bg-emerald-500/10 text-emerald-200',
                        'REJECTED' => 'border-rose-400/40 bg-rose-500/10 text-rose-200',
                        default => 'border-amber-400/40 bg-amber-500/10 text-amber-200',
                    };
                    $feeValueDisplay = $withdrawal->fee_type === 'percentage'
                        ? rtrim(rtrim(number_format((float) $withdrawal->fee_value, 4, '.', ''), '0'), '.').'%'
                        : number_format((float) $withdrawal->fee_value, 2).' GHS';
                    $localAmount = (float) ($withdrawal->local_amount ?? $withdrawal->amount);
                    $localCurrency = $withdrawal->local_currency ?? 'GHS';
                    $conversionRate = (float) ($withdrawal->conversion_rate ?? 1);
                    $localFeeAmount = (float) $withdrawal->fee_amount * $conversionRate;
                    $localNetAmount = (float) $withdrawal->net_amount * $conversionRate;
                @endphp

                <article class="card-muted">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Request #{{ $withdrawal->id }}</p>
                            <p class="font-medium text-slate-100">{{ $withdrawal->user->name }} <span class="text-slate-400">({{ $withdrawal->user->email }})</span></p>
                            <p class="mt-1 text-sm text-slate-300">{{ number_format($localAmount, 2) }} {{ $localCurrency }} from {{ $withdrawal->currency }} to {{ $provider }}</p>
                            <p class="mt-1 text-xs text-slate-400">Wallet hold {{ number_format((float) $withdrawal->amount, 8) }} {{ $withdrawal->currency }} | Fee {{ number_format((float) $withdrawal->fee_amount, 8) }} {{ $withdrawal->currency }} | Recipient gets {{ number_format((float) $withdrawal->net_amount, 8) }} {{ $withdrawal->currency }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-2 py-0.5 text-[11px] {{ $statusClass }}">{{ $withdrawal->status }}</span>
                            @if($withdrawal->status === 'PENDING')
                                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">
                                    @csrf
                                    <button class="btn-up text-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}">
                                    @csrf
                                    <button class="btn-down text-sm">Reject</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <details class="mt-3 rounded-xl border border-slate-700/70 bg-slate-950/60 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-slate-200">View Full Withdrawal Details</summary>
                        <div class="mt-3 grid gap-2 text-sm text-slate-300 sm:grid-cols-2 lg:grid-cols-3">
                            <p><span class="text-slate-400">Submitted:</span> {{ $withdrawal->created_at }}</p>
                            <p><span class="text-slate-400">Entered Amount:</span> {{ number_format($localAmount, 2) }} {{ $localCurrency }}</p>
                            <p><span class="text-slate-400">Conversion Rate:</span> 1 {{ $withdrawal->currency }} = {{ number_format($conversionRate, 2) }} {{ $localCurrency }}</p>
                            <p><span class="text-slate-400">Provider:</span> {{ $provider }}</p>
                            <p><span class="text-slate-400">Account Number:</span> {{ $accountNumber }}</p>
                            <p><span class="text-slate-400">Account Name:</span> {{ $accountName }}</p>
                            <p><span class="text-slate-400">Account Phone:</span> {{ $accountPhone }}</p>
                            <p><span class="text-slate-400">Wallet Currency:</span> {{ $withdrawal->currency }}</p>
                            <p><span class="text-slate-400">Wallet Hold:</span> {{ number_format((float) $withdrawal->amount, 8) }} {{ $withdrawal->currency }}</p>
                            <p><span class="text-slate-400">Fee Type:</span> {{ strtoupper($withdrawal->fee_type) }}</p>
                            <p><span class="text-slate-400">Fee Value:</span> {{ $feeValueDisplay }}</p>
                            <p><span class="text-slate-400">Fee Amount:</span> {{ number_format((float) $withdrawal->fee_amount, 8) }} {{ $withdrawal->currency }} ({{ number_format($localFeeAmount, 2) }} {{ $localCurrency }})</p>
                            <p><span class="text-slate-400">Recipient Gets:</span> {{ number_format($localNetAmount, 2) }} {{ $localCurrency }} ({{ number_format((float) $withdrawal->net_amount, 8) }} {{ $withdrawal->currency }})</p>
                            <p><span class="text-slate-400">Processed By:</span> {{ optional($withdrawal->approver)->email ?? '-' }}</p>
                            <p class="sm:col-span-2 lg:col-span-3"><span class="text-slate-400">Extra Note:</span> {{ $withdrawal->note ?: '-' }}</p>
                        </div>
                    </details>
                </article>
            @empty
                <p class="text-sm text-slate-400">No withdrawal requests found.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    </section>
</x-layouts.app>
