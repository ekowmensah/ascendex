<x-layouts.app :title="'Admin Deposits'">
    @include('admin.partials.nav', [
        'title' => 'Deposits',
        'subtitle' => 'Review incoming funding requests with full proof and sender metadata.',
        'stats' => $stats,
    ])

    <section class="glass-panel">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Deposit Review Queue</h2>
            <span class="rounded-full border border-slate-700/80 bg-slate-950/80 px-2 py-0.5 text-[11px] text-slate-300">{{ $deposits->total() }} requests</span>
        </div>

        <form method="GET" action="{{ route('admin.deposits.index') }}" class="mb-3 grid gap-2 md:grid-cols-2 xl:grid-cols-[1.2fr,0.6fr,0.6fr,0.7fr,0.7fr,auto,auto]">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search ID, user, reference, sender" class="input-dark">
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
            <button class="btn-primary" type="submit">Filter Deposits</button>
            <a href="{{ route('admin.deposits.index') }}" class="pill-link inline-flex items-center justify-center">Reset</a>
        </form>

        <div class="space-y-3">
            @forelse($deposits as $deposit)
                @php
                    $rawPaymentMethod = strtolower(trim((string) $deposit->payment_method));
                    $providerKey = strtolower(trim((string) ($deposit->mobile_provider ?: (str_contains($rawPaymentMethod, ':') ? explode(':', $rawPaymentMethod, 2)[1] : $rawPaymentMethod))));
                    if (in_array($providerKey, ['momo', 'mobile_money'], true)) {
                        $provider = 'MOBILE MONEY';
                    } else {
                        $provider = strtoupper(str_replace('_', ' ', $providerKey));
                    }

                    $isLegacyRecord = empty($deposit->sender_name) && empty($deposit->sender_phone) && empty($deposit->transaction_reference);
                    $senderNameDisplay = $deposit->sender_name ?: 'Not captured';
                    $senderPhoneDisplay = $deposit->sender_phone ?: 'Not captured';
                    $txRefDisplay = $deposit->transaction_reference ?: 'Not captured';

                    $statusClass = match ($deposit->status) {
                        'APPROVED' => 'border-emerald-400/40 bg-emerald-500/10 text-emerald-200',
                        'REJECTED' => 'border-rose-400/40 bg-rose-500/10 text-rose-200',
                        default => 'border-amber-400/40 bg-amber-500/10 text-amber-200',
                    };
                    $feeValueDisplay = $deposit->fee_type === 'percentage'
                        ? rtrim(rtrim(number_format((float) $deposit->fee_value, 4, '.', ''), '0'), '.').'%'
                        : number_format((float) $deposit->fee_value, 2).' GHS';
                    $localAmount = (float) ($deposit->local_amount ?? $deposit->amount);
                    $localCurrency = $deposit->local_currency ?? 'GHS';
                    $conversionRate = (float) ($deposit->conversion_rate ?? 1);
                    $localFeeAmount = (float) $deposit->fee_amount * $conversionRate;
                    $proofUrl = $deposit->proof_path ? route('admin.deposits.proof', $deposit) : null;
                    $proofDownloadUrl = $deposit->proof_path ? route('admin.deposits.proof', ['deposit' => $deposit, 'download' => 1]) : null;
                @endphp

                <article class="card-muted">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Request #{{ $deposit->id }}</p>
                            <p class="font-medium text-slate-100">{{ $deposit->user->name }} <span class="text-slate-400">({{ $deposit->user->email }})</span></p>
                            <p class="mt-1 text-sm text-slate-300">{{ number_format($localAmount, 2) }} {{ $localCurrency }} via {{ $provider }} to {{ $deposit->currency }} wallet</p>
                            <p class="mt-1 text-xs text-slate-400">Gross {{ number_format((float) $deposit->amount, 8) }} {{ $deposit->currency }} | Fee {{ number_format((float) $deposit->fee_amount, 8) }} {{ $deposit->currency }} | Wallet credit {{ number_format((float) $deposit->net_amount, 8) }} {{ $deposit->currency }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-2 py-0.5 text-[11px] {{ $statusClass }}">{{ $deposit->status }}</span>
                            @if($proofUrl)
                                <button
                                    type="button"
                                    class="pill-link js-open-proof"
                                    data-proof-url="{{ $proofUrl }}"
                                    data-proof-label="Deposit #{{ $deposit->id }} Proof"
                                >
                                    Open Proof
                                </button>
                                <a href="{{ $proofDownloadUrl }}" class="pill-link">Download</a>
                            @endif
                            @if($deposit->status === 'PENDING')
                                <form method="POST" action="{{ route('admin.deposits.approve', $deposit) }}">
                                    @csrf
                                    <button class="btn-up text-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}">
                                    @csrf
                                    <button class="btn-down text-sm">Reject</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <details class="mt-3 rounded-xl border border-slate-700/70 bg-slate-950/60 p-3">
                        <summary class="cursor-pointer text-sm font-medium text-slate-200">View Full Deposit Details</summary>
                        <div class="mt-3 grid gap-2 text-sm text-slate-300 sm:grid-cols-2 lg:grid-cols-3">
                            <p><span class="text-slate-400">Submitted:</span> {{ $deposit->created_at }}</p>
                            <p><span class="text-slate-400">Entered Amount:</span> {{ number_format($localAmount, 2) }} {{ $localCurrency }}</p>
                            <p><span class="text-slate-400">Conversion Rate:</span> 1 {{ $deposit->currency }} = {{ number_format($conversionRate, 2) }} {{ $localCurrency }}</p>
                            <p><span class="text-slate-400">Sender Name:</span> {{ $senderNameDisplay }}</p>
                            <p><span class="text-slate-400">Sender Phone:</span> {{ $senderPhoneDisplay }}</p>
                            <p><span class="text-slate-400">Transaction Ref:</span> {{ $txRefDisplay }}</p>
                            <p><span class="text-slate-400">Wallet Currency:</span> {{ $deposit->currency }}</p>
                            <p><span class="text-slate-400">Gross Wallet Amount:</span> {{ number_format((float) $deposit->amount, 8) }} {{ $deposit->currency }}</p>
                            <p><span class="text-slate-400">Fee Type:</span> {{ strtoupper($deposit->fee_type) }}</p>
                            <p><span class="text-slate-400">Fee Value:</span> {{ $feeValueDisplay }}</p>
                            <p><span class="text-slate-400">Fee Amount:</span> {{ number_format((float) $deposit->fee_amount, 8) }} {{ $deposit->currency }} ({{ number_format($localFeeAmount, 2) }} {{ $localCurrency }})</p>
                            <p><span class="text-slate-400">Wallet Credit:</span> {{ number_format((float) $deposit->net_amount, 8) }} {{ $deposit->currency }}</p>
                            <p>
                                <span class="text-slate-400">Proof File:</span>
                                @if($proofUrl)
                                    <button
                                        type="button"
                                        class="js-open-proof text-cyan-300 underline break-all text-left"
                                        data-proof-url="{{ $proofUrl }}"
                                        data-proof-label="Deposit #{{ $deposit->id }} Proof"
                                    >
                                        {{ $deposit->proof_path }}
                                    </button>
                                @else
                                    {{ $deposit->proof_path ?: '-' }}
                                @endif
                            </p>
                            <p><span class="text-slate-400">Processed By:</span> {{ optional($deposit->approver)->email ?? '-' }}</p>
                            <p class="sm:col-span-2 lg:col-span-3"><span class="text-slate-400">Extra Note:</span> {{ $deposit->note ?: '-' }}</p>
                            @if($isLegacyRecord)
                                <p class="sm:col-span-2 lg:col-span-3 rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                                    This is a legacy deposit record. Sender metadata was not stored in structured fields when this request was created.
                                </p>
                            @endif
                        </div>
                    </details>
                </article>
            @empty
                <p class="text-sm text-slate-400">No deposit requests found.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $deposits->links() }}</div>
    </section>

    <div id="proof-modal" class="fixed inset-0 z-50 hidden bg-slate-950/75 p-3 backdrop-blur-sm">
        <div class="mx-auto mt-4 w-full max-w-5xl rounded-2xl border border-slate-700/80 bg-slate-900/95 shadow-2xl shadow-cyan-950/40">
            <div class="flex items-center justify-between border-b border-slate-700/80 px-4 py-3">
                <h3 id="proof-modal-title" class="text-sm font-semibold text-slate-100">Proof Preview</h3>
                <button id="proof-modal-close" type="button" class="pill-link">Close</button>
            </div>
            <div class="h-[75vh] w-full">
                <iframe id="proof-modal-frame" class="h-full w-full rounded-b-2xl bg-white" title="Deposit proof preview"></iframe>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('proof-modal');
            const closeBtn = document.getElementById('proof-modal-close');
            const frame = document.getElementById('proof-modal-frame');
            const title = document.getElementById('proof-modal-title');
            const openButtons = document.querySelectorAll('.js-open-proof');

            if (!modal || !closeBtn || !frame || !title || !openButtons.length) {
                return;
            }

            const openModal = (url, label) => {
                frame.src = url;
                title.textContent = label || 'Proof Preview';
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                frame.src = '';
                document.body.classList.remove('overflow-hidden');
            };

            openButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    openModal(btn.dataset.proofUrl || '', btn.dataset.proofLabel || '');
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
</x-layouts.app>
