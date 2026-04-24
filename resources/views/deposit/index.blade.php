<x-layouts.app :title="'Deposits'">
    @php
        $walletOptionsByCurrency = collect($conversionConfig['wallet_options'] ?? [])->keyBy('wallet_currency');
        $selectedWalletCurrency = old('wallet_currency', $conversionConfig['selected_wallet_currency'] ?? 'USDT');
        $depositPreviewJs = [
            'feeType' => $depositFeeConfig['type'],
            'feeValue' => (float) $depositFeeConfig['value'],
            'walletOptions' => $walletOptionsByCurrency->mapWithKeys(fn ($option, $currency) => [
                $currency => [
                    'available' => (bool) ($option['available'] ?? false),
                    'label' => $option['label'] ?? $currency,
                    'ghsPerWalletUnit' => isset($option['ghs_per_wallet_unit']) ? (float) $option['ghs_per_wallet_unit'] : null,
                    'rateLabel' => $option['rate_label'] ?? 'Rate unavailable',
                    'freshnessLabel' => $option['freshness_label'] ?? 'Rate status unavailable',
                ],
            ])->all(),
            'walletBalances' => collect($wallets ?? [])->mapWithKeys(fn ($wallet, $currency) => [
                $currency => [
                    'balance' => (float) $wallet->balance,
                    'locked_balance' => (float) $wallet->locked_balance,
                ],
            ])->all(),
            'localCurrency' => $conversionConfig['local_currency'] ?? 'GHS',
            'defaultCurrency' => $selectedWalletCurrency,
        ];
        $selectedWallet = $walletOptionsByCurrency->get($selectedWalletCurrency) ?? $walletOptionsByCurrency->first();
    @endphp

    <section class="glass-panel mb-4">
        <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Funding</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Deposit Center</h1>
                <p class="mt-1 text-sm text-slate-400">Submit your mobile money amount in Ghana cedis, choose the wallet asset to fund, and review the exact conversion before you submit.</p>
            </div>
            <span class="rounded-full border border-cyan-400/40 bg-cyan-500/10 px-2 py-0.5 text-[11px] text-cyan-200">Manual Verification</span>
        </div>
    </section>

    <section class="glass-panel mb-4">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wide text-slate-400">Target Wallet</p>
                <p id="deposit-selected-wallet" class="mt-2 text-lg font-semibold text-cyan-200">{{ $selectedWallet['wallet_currency'] ?? 'USDT' }}</p>
                <p id="deposit-selected-wallet-label" class="mt-1 text-xs text-slate-400">{{ $selectedWallet['label'] ?? 'Tether (USDT)' }}</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wide text-slate-400">Current Rate</p>
                <p id="deposit-rate-label" class="mt-2 text-lg font-semibold text-cyan-200">{{ $selectedWallet['rate_label'] ?? 'Rate unavailable' }}</p>
                <p id="deposit-rate-age" class="mt-1 text-xs text-slate-400">{{ $selectedWallet['freshness_label'] ?? 'Rate status unavailable' }}</p>
                <p class="mt-1 text-xs text-slate-400">The submitted GHS amount is converted into the selected asset wallet using this snapped rate.</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wide text-slate-400">Fee Model</p>
                <p class="mt-2 text-lg font-semibold text-amber-200">{{ $depositFeeConfig['label'] }}</p>
                <p class="mt-1 text-xs text-slate-400">Flat fees are in GHS. Percentage fees use the submitted GHS amount before conversion.</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wide text-slate-400">Selected Wallet Balance</p>
                <p id="deposit-wallet-balance" class="mt-2 text-lg font-semibold text-slate-100">{{ number_format((float) optional(($wallets ?? collect())->get($selectedWalletCurrency))->balance, 8) }} {{ $selectedWalletCurrency }}</p>
                <p id="deposit-wallet-locked" class="mt-1 text-xs text-slate-400">Locked: {{ number_format((float) optional(($wallets ?? collect())->get($selectedWalletCurrency))->locked_balance, 8) }} {{ $selectedWalletCurrency }}</p>
            </div>
        </div>
    </section>

    <section class="glass-panel mb-4">
        <h2 class="mb-1 text-lg font-semibold">Submit Deposit</h2>
        <p class="mb-3 text-xs text-slate-400">Enter the amount you paid in GHS, then choose which wallet asset should be credited after approval.</p>

        <form id="deposit-form" method="POST" action="{{ route('deposit.store') }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
            <div>
                <label class="mb-1 block text-sm text-slate-300">Credit Wallet</label>
                <select id="deposit-wallet-currency" name="wallet_currency" class="input-dark" required>
                    @foreach(($conversionConfig['wallet_options'] ?? []) as $walletOption)
                        <option value="{{ $walletOption['wallet_currency'] }}" @selected($selectedWalletCurrency === $walletOption['wallet_currency']) @disabled(!($walletOption['available'] ?? false))>
                            {{ $walletOption['wallet_currency'] }}{{ !($walletOption['available'] ?? false) ? ' (rate unavailable)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Amount (GHS)</label>
                <input id="deposit-amount-input" type="number" name="amount" min="1" step="0.01" value="{{ old('amount') }}" class="input-dark" placeholder="e.g. 150.00" required>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Payment Method</label>
                <input type="text" value="Mobile Money" class="input-dark" disabled>
                <input type="hidden" name="payment_method" value="mobile_money">
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Provider</label>
                <select name="mobile_provider" class="input-dark" required>
                    <option value="mtn" @selected(old('mobile_provider', 'mtn') === 'mtn')>MTN Mobile Money</option>
                    <option value="airtel" @selected(old('mobile_provider') === 'airtel')>Airtel Money</option>
                    <option value="tigo" @selected(old('mobile_provider') === 'tigo')>Tigo Cash</option>
                    <option value="vodafone" @selected(old('mobile_provider') === 'vodafone')>Vodafone Cash</option>
                    <option value="other" @selected(old('mobile_provider') === 'other')>Other</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Sender Name</label>
                <input type="text" name="sender_name" value="{{ old('sender_name') }}" class="input-dark" placeholder="e.g. John Doe" required>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Sender Phone</label>
                <input type="text" name="sender_phone" value="{{ old('sender_phone') }}" class="input-dark" placeholder="e.g. +233501234567" required>
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Transaction Reference</label>
                <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}" class="input-dark" placeholder="e.g. MOMO-847392" required>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm text-slate-300">Payment Proof</label>
                <input type="file" name="proof" class="input-dark" required>
                <p class="mt-1 text-[11px] text-slate-500">Accepted: JPG, PNG, PDF (max 5MB)</p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm text-slate-300">Note</label>
                <textarea name="note" rows="2" class="input-dark">{{ old('note') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <div class="rounded-2xl border border-cyan-500/25 bg-cyan-500/8 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-cyan-300">Transaction Preview</p>
                            <p class="mt-1 text-sm text-slate-300">Review the GHS amount, selected wallet currency, fee, and estimated asset credit before submitting.</p>
                        </div>
                        <span class="rounded-full border border-cyan-400/30 bg-slate-950/70 px-2 py-0.5 text-[11px] text-cyan-200">Live update</span>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="card-muted">
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Submitted Amount</p>
                            <p id="deposit-preview-amount" class="mt-2 text-lg font-semibold text-slate-100">0.00 GHS</p>
                        </div>
                        <div class="card-muted">
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Gross Equivalent</p>
                            <p id="deposit-preview-gross" class="mt-2 text-lg font-semibold text-slate-100">0.00000000 USDT</p>
                        </div>
                        <div class="card-muted">
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Fee Charged</p>
                            <p id="deposit-preview-fee-wallet" class="mt-2 text-lg font-semibold text-amber-200">0.00000000 USDT</p>
                            <p id="deposit-preview-fee-local" class="mt-1 text-[11px] text-slate-400">0.00 GHS</p>
                        </div>
                        <div class="card-muted">
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Wallet Credit</p>
                            <p id="deposit-preview-net" class="mt-2 text-lg font-semibold text-emerald-200">0.00000000 USDT</p>
                        </div>
                    </div>

                    <p id="deposit-preview-note" class="mt-3 text-xs text-slate-400">Enter an amount and select a wallet currency to preview the exact credit.</p>
                    <p id="deposit-preview-warning" class="mt-2 hidden rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs text-rose-200">
                        This fee configuration would consume the full converted wallet amount. Increase the amount before submitting.
                    </p>
                </div>
            </div>
            <div class="sm:col-span-2">
                <button id="deposit-submit-btn" type="submit" class="btn-primary text-sm">Submit Deposit</button>
            </div>
        </form>
    </section>

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Deposit History</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $deposits->total() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dark min-w-[40rem]">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Wallet</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($deposits as $deposit)
                    @php
                        $provider = str_contains($deposit->payment_method, ':') ? explode(':', $deposit->payment_method)[1] : $deposit->payment_method;
                    @endphp
                    <tr>
                        <td class="font-medium">
                            {{ number_format((float) ($deposit->local_amount ?? $deposit->amount), 2) }} {{ $deposit->local_currency ?? 'GHS' }}
                            <div class="mt-1 text-[11px] text-slate-400">
                                Gross: {{ number_format((float) $deposit->amount, 8) }} {{ $deposit->currency }} |
                                Fee: {{ number_format((float) $deposit->fee_amount, 8) }} {{ $deposit->currency }} |
                                Credit: {{ number_format((float) $deposit->net_amount, 8) }} {{ $deposit->currency }}
                            </div>
                        </td>
                        <td class="uppercase text-xs tracking-wide text-slate-300">{{ $deposit->currency }}</td>
                        <td class="uppercase text-xs tracking-wide text-slate-300">{{ str_replace('_', ' ', $provider) }}</td>
                        <td>
                            <span class="rounded-full border px-2 py-0.5 text-[11px] {{ $deposit->status === 'APPROVED' ? 'border-emerald-400/40 bg-emerald-500/10 text-emerald-200' : ($deposit->status === 'REJECTED' ? 'border-rose-400/40 bg-rose-500/10 text-rose-200' : 'border-amber-400/40 bg-amber-500/10 text-amber-200') }}">
                                {{ $deposit->status }}
                            </span>
                        </td>
                        <td class="text-xs text-slate-400">{{ $deposit->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-400">No deposits submitted yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $deposits->links() }}</div>
    </section>

    <script>
        (function () {
            const form = document.getElementById('deposit-form');
            const submitBtn = document.getElementById('deposit-submit-btn');
            const amountInput = document.getElementById('deposit-amount-input');
            const walletCurrencyInput = document.getElementById('deposit-wallet-currency');
            const rateLabelEl = document.getElementById('deposit-rate-label');
            const rateAgeEl = document.getElementById('deposit-rate-age');
            const selectedWalletEl = document.getElementById('deposit-selected-wallet');
            const selectedWalletLabelEl = document.getElementById('deposit-selected-wallet-label');
            const walletBalanceEl = document.getElementById('deposit-wallet-balance');
            const walletLockedEl = document.getElementById('deposit-wallet-locked');
            const previewAmountEl = document.getElementById('deposit-preview-amount');
            const previewGrossEl = document.getElementById('deposit-preview-gross');
            const previewFeeWalletEl = document.getElementById('deposit-preview-fee-wallet');
            const previewFeeLocalEl = document.getElementById('deposit-preview-fee-local');
            const previewNetEl = document.getElementById('deposit-preview-net');
            const previewNoteEl = document.getElementById('deposit-preview-note');
            const previewWarningEl = document.getElementById('deposit-preview-warning');
            const previewConfig = @json($depositPreviewJs);

            if (!form || !submitBtn || !amountInput || !walletCurrencyInput) {
                return;
            }

            const round8 = (value) => Math.round((value + Number.EPSILON) * 100000000) / 100000000;
            const formatLocalAmount = (value) => `${Number.isFinite(value) ? value.toFixed(2) : '0.00'} ${previewConfig.localCurrency || 'GHS'}`;
            const formatWalletAmount = (value, currency) => `${Number.isFinite(value) ? value.toFixed(8) : '0.00000000'} ${currency || 'USDT'}`;
            const getWalletOption = () => previewConfig.walletOptions?.[walletCurrencyInput.value] || null;
            const getWalletBalance = () => previewConfig.walletBalances?.[walletCurrencyInput.value] || { balance: 0, locked_balance: 0 };
            const calculateLocalFee = (localAmount) => {
                if (!Number.isFinite(localAmount) || localAmount <= 0) {
                    return 0;
                }

                if (previewConfig.feeType === 'percentage') {
                    return round8((localAmount * Number(previewConfig.feeValue || 0)) / 100);
                }

                return round8(Number(previewConfig.feeValue || 0));
            };

            const refreshEstimate = () => {
                const localAmount = Number.parseFloat(amountInput.value || '0');
                const walletOption = getWalletOption();
                const walletBalance = getWalletBalance();
                const walletCurrency = walletCurrencyInput.value;
                const rate = Number(walletOption?.ghsPerWalletUnit || 0);
                const rateAvailable = Boolean(walletOption?.available) && Number.isFinite(rate) && rate > 0;
                const grossWalletAmount = rateAvailable && Number.isFinite(localAmount) && localAmount > 0 ? round8(localAmount / rate) : 0;
                const feeLocal = calculateLocalFee(localAmount);
                const feeWallet = rateAvailable && feeLocal > 0 ? round8(feeLocal / rate) : 0;
                const netWallet = round8(Math.max(grossWalletAmount - feeWallet, 0));
                const validAmount = Number.isFinite(localAmount) && localAmount > 0;
                const wouldConsumeAll = validAmount && netWallet <= 0;

                if (selectedWalletEl) {
                    selectedWalletEl.textContent = walletCurrency;
                }

                if (selectedWalletLabelEl) {
                    selectedWalletLabelEl.textContent = walletOption?.label || walletCurrency;
                }

                if (rateLabelEl) {
                    rateLabelEl.textContent = walletOption?.rateLabel || 'Rate unavailable';
                }

                if (rateAgeEl) {
                    rateAgeEl.textContent = walletOption?.freshnessLabel || 'Rate status unavailable';
                }

                if (walletBalanceEl) {
                    walletBalanceEl.textContent = formatWalletAmount(Number(walletBalance.balance || 0), walletCurrency);
                }

                if (walletLockedEl) {
                    walletLockedEl.textContent = `Locked: ${formatWalletAmount(Number(walletBalance.locked_balance || 0), walletCurrency)}`;
                }

                if (previewAmountEl) {
                    previewAmountEl.textContent = formatLocalAmount(validAmount ? localAmount : 0);
                }

                if (previewGrossEl) {
                    previewGrossEl.textContent = formatWalletAmount(grossWalletAmount, walletCurrency);
                }

                if (previewFeeWalletEl) {
                    previewFeeWalletEl.textContent = formatWalletAmount(feeWallet, walletCurrency);
                }

                if (previewFeeLocalEl) {
                    previewFeeLocalEl.textContent = formatLocalAmount(feeLocal);
                }

                if (previewNetEl) {
                    previewNetEl.textContent = formatWalletAmount(netWallet, walletCurrency);
                }

                if (previewNoteEl) {
                    previewNoteEl.textContent = !validAmount
                        ? 'Enter an amount and select a wallet currency to preview the exact credit.'
                        : !rateAvailable
                            ? `The ${walletCurrency} conversion rate is unavailable right now, so this request cannot be submitted yet.`
                            : `${formatLocalAmount(localAmount)} converts to ${formatWalletAmount(grossWalletAmount, walletCurrency)} before fees, and the estimated wallet credit is ${formatWalletAmount(netWallet, walletCurrency)}.`;
                }

                if (previewWarningEl) {
                    previewWarningEl.classList.toggle('hidden', !wouldConsumeAll || !rateAvailable);
                }

                const shouldDisable = !rateAvailable || wouldConsumeAll;
                submitBtn.disabled = shouldDisable;
                submitBtn.classList.toggle('opacity-70', shouldDisable);
                submitBtn.classList.toggle('cursor-not-allowed', shouldDisable);
            };

            walletCurrencyInput.addEventListener('change', refreshEstimate);
            amountInput.addEventListener('input', refreshEstimate);
            refreshEstimate();

            form.addEventListener('submit', (event) => {
                const localAmount = Number.parseFloat(amountInput.value || '0');
                const walletOption = getWalletOption();
                const walletCurrency = walletCurrencyInput.value;
                const rate = Number(walletOption?.ghsPerWalletUnit || 0);
                const rateAvailable = Boolean(walletOption?.available) && Number.isFinite(rate) && rate > 0;
                const grossWalletAmount = rateAvailable && Number.isFinite(localAmount) && localAmount > 0 ? round8(localAmount / rate) : 0;
                const feeLocal = calculateLocalFee(localAmount);
                const feeWallet = rateAvailable && feeLocal > 0 ? round8(feeLocal / rate) : 0;
                const netWallet = round8(Math.max(grossWalletAmount - feeWallet, 0));

                if (!rateAvailable || !Number.isFinite(localAmount) || localAmount <= 0 || netWallet <= 0) {
                    event.preventDefault();

                    return;
                }

                const confirmed = window.confirm(
                    `Deposit preview\n\nTarget wallet: ${walletCurrency}\nEntered amount: ${formatLocalAmount(localAmount)}\nConversion rate: ${walletOption.rateLabel}\nGross wallet equivalent: ${formatWalletAmount(grossWalletAmount, walletCurrency)}\nFee charged: ${formatLocalAmount(feeLocal)} (${formatWalletAmount(feeWallet, walletCurrency)})\nEstimated wallet credit: ${formatWalletAmount(netWallet, walletCurrency)}\n\nSubmit this deposit request?`
                );

                if (!confirmed) {
                    event.preventDefault();

                    return;
                }

                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                submitBtn.textContent = 'Submitting...';
            });
        })();
    </script>
</x-layouts.app>
