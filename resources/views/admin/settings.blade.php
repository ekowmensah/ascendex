<x-layouts.app :title="'Admin Settings'">
    @include('admin.partials.nav', [
        'title' => 'Settings',
        'subtitle' => 'Configure payout policy, the base GHS to USDT rate, and shared funding or withdrawal fees.',
        'stats' => $stats,
    ])

    <div class="grid gap-4 lg:grid-cols-[1.1fr,0.9fr]">
        <section class="glass-panel">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Funding & Withdrawal Fees</h2>
                    <p class="mt-1 text-xs text-slate-400">Percentage fees apply to the submitted GHS amount. Flat fees are fixed GHS charges before the request is converted into the selected wallet asset.</p>
                </div>
                <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">Applies to new requests</span>
            </div>

            <form method="POST" action="{{ route('admin.settings.fees') }}" class="mt-4 grid gap-4">
                @csrf

                <div class="card-muted grid gap-3 sm:grid-cols-[0.6fr,0.4fr,auto] sm:items-end">
                    <div class="sm:col-span-3">
                        <h3 class="text-sm font-semibold text-slate-100">Deposit Fee</h3>
                        <p class="mt-1 text-xs text-slate-400">Deducted from the converted asset amount when an admin approves a deposit.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Type</label>
                        <select name="deposit_fee_type" class="input-dark" required>
                            <option value="percentage" @selected(old('deposit_fee_type', $depositFeeConfig['type']) === 'percentage')>Percentage</option>
                            <option value="flat" @selected(old('deposit_fee_type', $depositFeeConfig['type']) === 'flat')>Flat (GHS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Value</label>
                        <input
                            type="number"
                            name="deposit_fee_value"
                            min="0"
                            step="0.00000001"
                            value="{{ old('deposit_fee_value', $depositFeeConfig['value']) }}"
                            class="input-dark"
                            required
                        >
                    </div>
                    <button class="btn-primary sm:self-end">Save Fee Settings</button>
                </div>

                <div class="card-muted grid gap-3 sm:grid-cols-[0.6fr,0.4fr,auto] sm:items-end">
                    <div class="sm:col-span-3">
                        <h3 class="text-sm font-semibold text-slate-100">Withdrawal Fee</h3>
                        <p class="mt-1 text-xs text-slate-400">Deducted from the submitted GHS payout request while the selected wallet still holds the full gross asset equivalent.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Type</label>
                        <select name="withdrawal_fee_type" class="input-dark" required>
                            <option value="percentage" @selected(old('withdrawal_fee_type', $withdrawalFeeConfig['type']) === 'percentage')>Percentage</option>
                            <option value="flat" @selected(old('withdrawal_fee_type', $withdrawalFeeConfig['type']) === 'flat')>Flat (GHS)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Value</label>
                        <input
                            type="number"
                            name="withdrawal_fee_value"
                            min="0"
                            step="0.00000001"
                            value="{{ old('withdrawal_fee_value', $withdrawalFeeConfig['value']) }}"
                            class="input-dark"
                            required
                        >
                    </div>
                    <button class="btn-primary sm:self-end">Save Fee Settings</button>
                </div>
            </form>
        </section>

        <section class="glass-panel">
            <h2 class="mb-3 text-base font-semibold">Base Conversion</h2>
            <form method="POST" action="{{ route('admin.settings.conversion') }}" class="grid gap-3 sm:grid-cols-[0.55fr,auto] sm:items-end">
                @csrf
                <div>
                    <label class="mb-1 block text-sm text-slate-300">GHS Per 1 USDT</label>
                    <input
                        type="number"
                        name="ghs_per_usdt"
                        min="0.00000001"
                        step="0.00000001"
                        value="{{ old('ghs_per_usdt', $conversionConfig['ghs_per_usdt']) }}"
                        class="input-dark"
                        required
                    >
                </div>
                <button class="btn-primary">Save Base Rate</button>
            </form>
            <p class="mt-3 text-xs text-slate-400">USDT uses this rate directly. BTC and ETH wallet rates are derived from this base rate multiplied by the latest market price in USDT.</p>
        </section>

        <section class="glass-panel">
            <h2 class="mb-3 text-base font-semibold">Payout Configuration</h2>
            <form method="POST" action="{{ route('admin.settings.payout') }}" class="grid gap-3 sm:grid-cols-[0.45fr,auto] sm:items-end">
                @csrf
                <div>
                    <label class="mb-1 block text-sm text-slate-300">Payout % (70-90)</label>
                    <input type="number" name="payout_percent" min="70" max="90" step="0.1" value="{{ $payoutPercent }}" class="input-dark" required>
                </div>
                <button class="btn-primary">Save Payout</button>
            </form>
            <p class="mt-3 text-xs text-slate-400">Changes apply to newly created trades. Payouts are settled back into whichever wallet currency the trade used.</p>
        </section>

        <section class="glass-panel">
            <h2 class="mb-3 text-base font-semibold">Current Runtime Values</h2>
            <div class="space-y-2 text-sm">
                @foreach($conversionConfig['wallet_options'] as $walletOption)
                    <div class="card-muted flex items-center justify-between gap-3">
                        <span class="text-slate-300">{{ $walletOption['wallet_currency'] }} Rate</span>
                        <span class="font-semibold text-cyan-200">
                            {{ $walletOption['available'] ? $walletOption['rate_label'] : 'Unavailable' }}
                        </span>
                    </div>
                @endforeach
                <div class="card-muted flex items-center justify-between">
                    <span class="text-slate-300">Deposit Fee</span>
                    <span class="font-semibold text-cyan-200">{{ $depositFeeConfig['label'] }}</span>
                </div>
                <div class="card-muted flex items-center justify-between">
                    <span class="text-slate-300">Withdrawal Fee</span>
                    <span class="font-semibold text-cyan-200">{{ $withdrawalFeeConfig['label'] }}</span>
                </div>
                <div class="card-muted flex items-center justify-between">
                    <span class="text-slate-300">Payout Percent</span>
                    <span class="font-semibold text-cyan-200">{{ number_format((float) $payoutPercent, 2) }}%</span>
                </div>
                <div class="card-muted flex items-center justify-between">
                    <span class="text-slate-300">Payout Rate</span>
                    <span class="font-semibold text-cyan-200">{{ number_format((float) $payoutRate, 4) }}</span>
                </div>
                <div class="card-muted flex items-center justify-between">
                    <span class="text-slate-300">Pending Trades</span>
                    <span class="font-semibold text-amber-200">{{ $stats['pending_trades'] }}</span>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
