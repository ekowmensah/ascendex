<x-layouts.app :title="'Trade'">
    @php
        $defaultTradeCurrency = $selectedTradeCurrency ?? 'USDT';
        $selectedTradeWallet = $wallets->get($defaultTradeCurrency) ?? $wallets->first();
    @endphp
    <script src="https://unpkg.com/lightweight-charts@5.1.0/dist/lightweight-charts.standalone.production.js"></script>

    <div class="grid gap-4 xl:grid-cols-[1.45fr,0.55fr]">
        <section class="glass-panel">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Execution Terminal</p>
                    <h1 class="text-xl font-semibold">Contract Trading Desk</h1>
                    <p class="text-sm text-slate-400">Live candlestick feed with trade-time countdown</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <button data-symbol="BTCUSDT" class="symbol-btn rounded-xl border border-cyan-400/50 bg-cyan-500/20 px-3 py-1.5 text-cyan-200">BTCUSDT</button>
                    <button data-symbol="ETHUSDT" class="symbol-btn rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-1.5 text-slate-300">ETHUSDT</button>
                </div>
            </div>

            <div class="mb-3 grid gap-3 md:grid-cols-3">
                <div class="card-muted">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Current Price</p>
                    <p id="current-price" class="mt-1 text-3xl font-bold text-cyan-300">{{ number_format((float) $btcPrice, 2) }}</p>
                    <p id="price-change" class="mt-1 text-sm font-semibold text-emerald-400">+0.00%</p>
                    <p id="current-price-status" class="mt-1 text-xs {{ ($symbolStatusMap['BTCUSDT']['is_fresh'] ?? false) ? 'text-emerald-300' : 'text-amber-300' }}">
                        {{ ($symbolStatusMap['BTCUSDT']['is_fresh'] ?? false) ? 'Feed fresh: ' : 'Feed stale: ' }}{{ $symbolStatusMap['BTCUSDT']['age_label'] ?? 'No market tick recorded yet' }}
                    </p>
                </div>
                <div class="card-muted">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Selected Wallet</p>
                    <p id="selected-wallet-balance" class="mt-1 text-2xl font-semibold text-slate-100">{{ number_format((float) optional($selectedTradeWallet)->balance, 8) }} {{ optional($selectedTradeWallet)->currency ?? $defaultTradeCurrency }}</p>
                    <p id="selected-wallet-locked" class="mt-1 text-xs text-slate-400">Locked: {{ number_format((float) optional($selectedTradeWallet)->locked_balance, 8) }} {{ optional($selectedTradeWallet)->currency ?? $defaultTradeCurrency }}</p>
                </div>
                <div class="card-muted text-sm">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Session Stats</p>
                    <div class="mt-2 space-y-1 text-slate-300">
                        <p>High: <span id="stat-high">-</span></p>
                        <p>Low: <span id="stat-low">-</span></p>
                        <p>Volume: <span id="stat-volume">-</span></p>
                        <p>Open Trades: <span id="open-count-main">0</span></p>
                    </div>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap gap-2 text-sm">
                <button data-timeframe="1m" class="tf-btn rounded-lg border border-cyan-400/50 bg-cyan-500/20 px-3 py-1 text-cyan-200">1m</button>
                <button data-timeframe="5m" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">5m</button>
                <button data-timeframe="15m" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">15m</button>
                <button data-timeframe="30m" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">30m</button>
                <button data-timeframe="1h" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">1h</button>
                <button data-timeframe="4h" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">4h</button>
                <button data-timeframe="1d" class="tf-btn rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">1d</button>
            </div>

            <div class="mb-2 rounded-lg border border-slate-800/80 bg-slate-950/70 px-3 py-2 text-sm text-slate-300" id="ohlc-line">Time: - Open: - High: - Low: - Close: - Volume: -</div>

            <div class="relative overflow-hidden rounded-2xl border border-slate-800/80 bg-slate-950/80">
                <div id="chart" class="relative z-0 h-[500px] w-full"></div>

                <div class="trade-mobile-chart-actions pointer-events-none absolute inset-x-3 bottom-24 z-20">
                    <div class="pointer-events-auto flex items-center justify-center gap-2">
                        <button id="open-modal-up-mobile" type="button" class="btn-up w-32 py-1 text-xs shadow-lg shadow-lime-900/40">Buy Up</button>
                        <button id="open-modal-down-mobile" type="button" class="btn-down w-32 py-1 text-xs shadow-lg shadow-rose-900/40">Buy Down</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="glass-panel">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">Trade Ticket</h2>
                        <p class="mt-1 text-xs text-slate-400">Open a focused order modal for faster mobile execution.</p>
                    </div>
                    <span class="rounded-full border border-cyan-400/40 bg-cyan-500/10 px-2 py-1 text-[11px] font-medium text-cyan-200">Live</span>
                </div>

                <div class="mb-3 trade-desktop-ticket-actions">
                    <button id="open-modal-up" type="button" class="btn-up w-36 py-1.5 text-sm">Buy Up</button>
                    <button id="open-modal-down" type="button" class="btn-down w-36 py-1.5 text-sm">Buy Down</button>
                </div>

                <!-- <button id="open-trade-modal" type="button" class="btn-primary w-full trade-desktop-only">Open Trade</button> -->
            </div>

            <div class="glass-panel">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Open Positions</h2>
                    <span id="open-count-badge" class="rounded-full border border-amber-400/40 bg-amber-500/10 px-2 py-0.5 text-xs text-amber-200">0</span>
                </div>
                <div id="open-positions-list" class="space-y-2 text-sm">
                    <p class="text-slate-400">No active positions.</p>
                </div>
            </div>

            <div class="glass-panel text-sm">
                <p class="text-xs uppercase tracking-wider text-slate-400">Execution Notes</p>
                <p class="mt-1 text-slate-300">Pending trades show live countdown. After expiry, status updates automatically to WIN/LOSE.</p>
            </div>

            <div id="settlement-health" class="hidden rounded-xl border border-amber-400/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-200"></div>
        </section>
    </div>

    <section class="glass-panel mt-4">
        <div class="mb-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wider text-slate-400">Pending</p>
                <p id="summary-pending" class="mt-1 text-xl font-semibold text-amber-300">0</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wider text-slate-400">Settled</p>
                <p id="summary-settled" class="mt-1 text-xl font-semibold text-slate-100">0</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wider text-slate-400">Win Rate</p>
                <p id="summary-winrate" class="mt-1 text-xl font-semibold text-cyan-300">0%</p>
            </div>
            <div class="card-muted">
                <p class="text-xs uppercase tracking-wider text-slate-400">Net P/L</p>
                <p id="summary-net" class="mt-1 text-xl font-semibold text-slate-100">0.00000000 {{ $defaultTradeCurrency }}</p>
            </div>
        </div>

        <h2 class="mb-3 text-base font-semibold">Recent Trades</h2>
        <div class="overflow-auto">
            <table class="table-dark">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
                    <th>Wallet</th>
                    <th>Direction</th>
                    <th>Amount</th>
                    <th>Entry</th>
                    <th>Close</th>
                    <th>Expires</th>
                    <th>P/L</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody id="recent-trades-body">
                    <tr>
                        <td colspan="10" class="px-3 py-3 text-slate-400">Loading trades...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <button id="trade-launcher" type="button" class="floating-ticket-fab trade-desktop-only" aria-controls="trade-modal-backdrop" aria-expanded="false">
        Trade
    </button>

    <div id="trade-modal-backdrop" class="trade-modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="trade-modal-title">
        <div class="trade-modal-panel">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-cyan-300">Execution</p>
                    <h3 id="trade-modal-title" class="text-lg font-semibold">Place Trade</h3>
                </div>
                <button id="close-trade-modal" type="button" class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-300 hover:border-slate-500">Close</button>
            </div>

            <div class="mb-3 grid gap-2 rounded-xl border border-slate-800/80 bg-slate-950/70 p-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Symbol</p>
                    <p id="modal-symbol" class="font-semibold text-slate-100">BTCUSDT</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Mark Price</p>
                    <p id="modal-price" class="font-semibold text-cyan-300">{{ number_format((float) $btcPrice, 2) }}</p>
                    <p id="modal-price-status" class="mt-1 text-[11px] {{ ($symbolStatusMap['BTCUSDT']['is_fresh'] ?? false) ? 'text-emerald-300' : 'text-amber-300' }}">
                        {{ ($symbolStatusMap['BTCUSDT']['is_fresh'] ?? false) ? 'Feed fresh: ' : 'Feed stale: ' }}{{ $symbolStatusMap['BTCUSDT']['age_label'] ?? 'No market tick recorded yet' }}
                    </p>
                </div>
            </div>

            <form id="trade-form" method="POST" action="{{ route('trade.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                <input type="hidden" name="symbol" id="trade-symbol" value="BTCUSDT">
                <input type="hidden" name="direction" id="trade-direction" value="UP">

                <div>
                    <label class="mb-1 block text-sm text-slate-300">Trade Wallet</label>
                    <select id="trade-wallet-currency" name="wallet_currency" class="input-dark" required>
                        @foreach($walletCurrencyOptions as $walletCurrency => $walletOption)
                            @if($walletOption['available'] ?? false)
                                <option value="{{ $walletCurrency }}" @selected($defaultTradeCurrency === $walletCurrency)>{{ $walletCurrency }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label id="trade-amount-label" class="mb-1 block text-sm text-slate-300">Amount ({{ $defaultTradeCurrency }})</label>
                    <input id="trade-amount" name="amount" type="number" step="0.00000001" min="0.00000001" required class="input-dark" placeholder="e.g. 0.50000000">
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-300">Duration</label>
                    <select id="trade-duration" name="duration" class="input-dark">
                        <option value="1">1 minute</option>
                        <option value="5">5 minutes</option>
                        <option value="15">15 minutes</option>
                    </select>
                </div>

                <p id="modal-direction-hint" class="rounded-lg border border-slate-700/80 bg-slate-950/70 px-3 py-2 text-xs text-slate-300">Direction: Buy Up</p>

                <button id="modal-submit" type="submit" class="btn-primary w-full py-2 text-sm">Place Trade</button>
            </form>
        </div>
    </div>

    <script>
        const baseSeries = {
            BTCUSDT: @json($btcSeries),
            ETHUSDT: @json($ethSeries),
        };

        const walletsByCurrency = @json($walletsPayload);
        const defaultTradeCurrency = @json($defaultTradeCurrency);
        const initialRecentTrades = @json($recentTradesPayload);
        const marketStatusBySymbol = @json($symbolStatusMap);
        const priceApiEndpoint = @json(url('/api/prices/latest'));
        const recentTradesEndpoint = @json(route('trade.recent'));
        const chartHistoryLimit = 1200;
        const pricesPollIntervalMs = 15000;
        const tradesPollIntervalMs = 3000;

        const timeframeMap = {
            '1m': 60,
            '5m': 300,
            '15m': 900,
            '30m': 1800,
            '1h': 3600,
            '4h': 14400,
            '1d': 86400,
        };

        let currentSymbol = 'BTCUSDT';
        let currentTimeframe = '1m';
        let currentTradeCurrency = defaultTradeCurrency;
        let currentDisplayPrice = Number({{ (float) $btcPrice }});
        let latestTrades = Array.isArray(initialRecentTrades) ? initialRecentTrades : [];

        const chartContainer = document.getElementById('chart');
        const currentPriceEl = document.getElementById('current-price');
        const priceChangeEl = document.getElementById('price-change');
        const currentPriceStatusEl = document.getElementById('current-price-status');
        const ohlcLineEl = document.getElementById('ohlc-line');
        const recentTradesBodyEl = document.getElementById('recent-trades-body');
        const openPositionsListEl = document.getElementById('open-positions-list');
        const openCountMainEl = document.getElementById('open-count-main');
        const openCountBadgeEl = document.getElementById('open-count-badge');
        const settlementHealthEl = document.getElementById('settlement-health');
        const tradeFormEl = document.getElementById('trade-form');
        const primaryAmountInput = document.getElementById('trade-amount');
        const primaryAmountLabel = document.getElementById('trade-amount-label');
        const primaryDurationInput = document.getElementById('trade-duration');
        const primarySymbolInput = document.getElementById('trade-symbol');
        const tradeDirectionInput = document.getElementById('trade-direction');
        const tradeWalletCurrencyInput = document.getElementById('trade-wallet-currency');
        const selectedWalletBalanceEl = document.getElementById('selected-wallet-balance');
        const selectedWalletLockedEl = document.getElementById('selected-wallet-locked');

        const tradeLauncherEl = document.getElementById('trade-launcher');
        const tradeModalBackdropEl = document.getElementById('trade-modal-backdrop');
        const closeTradeModalEl = document.getElementById('close-trade-modal');
        const openTradeModalEl = document.getElementById('open-trade-modal');
        const openModalUpEl = document.getElementById('open-modal-up');
        const openModalDownEl = document.getElementById('open-modal-down');
        const openModalUpMobileEl = document.getElementById('open-modal-up-mobile');
        const openModalDownMobileEl = document.getElementById('open-modal-down-mobile');
        const modalSymbolEl = document.getElementById('modal-symbol');
        const modalPriceEl = document.getElementById('modal-price');
        const modalPriceStatusEl = document.getElementById('modal-price-status');
        const modalDirectionHintEl = document.getElementById('modal-direction-hint');
        const modalSubmitEl = document.getElementById('modal-submit');

        function lastOf(items) {
            if (!Array.isArray(items) || items.length === 0) {
                return null;
            }

            return items[items.length - 1];
        }

        const initialLastPoint = lastOf(baseSeries.BTCUSDT || []);
        if (initialLastPoint && Number.isFinite(Number(initialLastPoint.value))) {
            currentDisplayPrice = Number(initialLastPoint.value);
        }

        const chartRuntime = {
            available: false,
            chart: null,
            candleSeries: null,
            volumeSeries: null,
        };

        function initChartRuntime() {
            if (!chartContainer) {
                return;
            }

            if (typeof window.LightweightCharts === 'undefined') {
                chartContainer.innerHTML = '<div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-400">Chart library unavailable. Check internet/CDN and refresh.</div>';
                return;
            }

            const chart = window.LightweightCharts.createChart(chartContainer, {
                width: chartContainer.clientWidth,
                height: chartContainer.clientHeight,
                layout: { background: { color: '#0b1220' }, textColor: '#94a3b8' },
                grid: { vertLines: { color: '#1e293b' }, horzLines: { color: '#1e293b' } },
                timeScale: { timeVisible: true, secondsVisible: false, borderColor: '#334155' },
                rightPriceScale: { borderColor: '#334155' },
                crosshair: {
                    mode: 0,
                    vertLine: { color: '#334155', width: 1, style: 2 },
                    horzLine: { color: '#334155', width: 1, style: 2 },
                },
            });

            const candleSeries = typeof chart.addCandlestickSeries === 'function'
                ? chart.addCandlestickSeries({
                    upColor: '#22c55e',
                    downColor: '#f43f5e',
                    wickUpColor: '#22c55e',
                    wickDownColor: '#f43f5e',
                    borderVisible: false,
                })
                : chart.addSeries(window.LightweightCharts.CandlestickSeries, {
                    upColor: '#22c55e',
                    downColor: '#f43f5e',
                    wickUpColor: '#22c55e',
                    wickDownColor: '#f43f5e',
                    borderVisible: false,
                });

            const volumeSeries = typeof chart.addHistogramSeries === 'function'
                ? chart.addHistogramSeries({
                    priceFormat: { type: 'volume' },
                    priceScaleId: '',
                    lastValueVisible: false,
                    priceLineVisible: false,
                })
                : chart.addSeries(window.LightweightCharts.HistogramSeries, {
                    priceFormat: { type: 'volume' },
                    priceScaleId: '',
                    lastValueVisible: false,
                    priceLineVisible: false,
                });

            chart.priceScale('').applyOptions({
                scaleMargins: { top: 0.82, bottom: 0 },
            });

            chartRuntime.available = true;
            chartRuntime.chart = chart;
            chartRuntime.candleSeries = candleSeries;
            chartRuntime.volumeSeries = volumeSeries;
        }

        initChartRuntime();

        function formatNum(value, decimals = 2) {
            const n = Number(value);
            if (!Number.isFinite(n)) {
                return '-';
            }
            return n.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        function formatCompactNum(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) {
                return '-';
            }
            return n.toLocaleString(undefined, { maximumFractionDigits: 0 });
        }

        function formatAssetAmount(value, currency) {
            const n = Number(value);
            if (!Number.isFinite(n)) {
                return `0.00000000 ${currency || currentTradeCurrency}`;
            }

            return `${n.toFixed(8)} ${currency || currentTradeCurrency}`;
        }

        function walletPayload(currency) {
            return walletsByCurrency?.[currency] || {
                currency,
                balance: 0,
                locked_balance: 0,
            };
        }

        function updateSelectedWalletDisplay(currency) {
            currentTradeCurrency = currency || currentTradeCurrency;
            const wallet = walletPayload(currentTradeCurrency);

            if (selectedWalletBalanceEl) {
                selectedWalletBalanceEl.textContent = formatAssetAmount(wallet.balance, currentTradeCurrency);
            }

            if (selectedWalletLockedEl) {
                selectedWalletLockedEl.textContent = `Locked: ${formatAssetAmount(wallet.locked_balance, currentTradeCurrency)}`;
            }

            if (primaryAmountLabel) {
                primaryAmountLabel.textContent = `Amount (${currentTradeCurrency})`;
            }
        }

        function symbolStatus(symbol) {
            return marketStatusBySymbol?.[symbol] || {
                symbol,
                is_fresh: false,
                age_label: 'No market tick recorded yet',
            };
        }

        function updateTradeSubmitAvailability() {
            if (!modalSubmitEl) {
                return;
            }

            const status = symbolStatus(currentSymbol);
            const isSubmitting = modalSubmitEl.dataset.submitting === 'true';
            const disabled = !status?.is_fresh || isSubmitting;

            modalSubmitEl.disabled = disabled;
            modalSubmitEl.classList.toggle('opacity-70', disabled);
            modalSubmitEl.classList.toggle('cursor-not-allowed', disabled);
        }

        function updatePriceFreshnessDisplay() {
            const status = symbolStatus(currentSymbol);
            const message = `${status?.is_fresh ? 'Feed fresh: ' : 'Feed stale: '}${status?.age_label || 'No market tick recorded yet'}`;
            const className = `mt-1 text-xs ${status?.is_fresh ? 'text-emerald-300' : 'text-amber-300'}`;

            if (currentPriceStatusEl) {
                currentPriceStatusEl.textContent = message;
                currentPriceStatusEl.className = className;
            }

            if (modalPriceStatusEl) {
                modalPriceStatusEl.textContent = message;
                modalPriceStatusEl.className = className.replace('text-xs', 'text-[11px]');
            }

            updateTradeSubmitAvailability();
        }

        function normalizeChartTime(time) {
            if (typeof time === 'number') {
                return time;
            }

            if (time && typeof time === 'object' && Number.isFinite(time.year) && Number.isFinite(time.month) && Number.isFinite(time.day)) {
                return Math.floor(Date.UTC(time.year, time.month - 1, time.day) / 1000);
            }

            return null;
        }

        function formatTimeLabel(unixTime) {
            if (!unixTime) {
                return '-';
            }
            const d = new Date(Number(unixTime) * 1000);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;
        }

        function formatCountdown(seconds) {
            const sec = Math.max(0, Math.floor(seconds));
            const hrs = Math.floor(sec / 3600);
            const mins = Math.floor((sec % 3600) / 60);
            const rem = sec % 60;

            if (hrs > 0) {
                return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
            }

            return `${String(mins).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
        }

        function buildCandles(points, timeframeSeconds) {
            const buckets = new Map();
            const sorted = [...(points || [])].sort((a, b) => Number(a.time) - Number(b.time));

            for (const point of sorted) {
                const time = Number(point.time);
                const value = Number(point.value);

                if (!Number.isFinite(time) || !Number.isFinite(value)) {
                    continue;
                }

                const bucket = Math.floor(time / timeframeSeconds) * timeframeSeconds;

                if (!buckets.has(bucket)) {
                    buckets.set(bucket, {
                        time: bucket,
                        open: value,
                        high: value,
                        low: value,
                        close: value,
                        volume: 1,
                    });
                    continue;
                }

                const candle = buckets.get(bucket);
                candle.high = Math.max(candle.high, value);
                candle.low = Math.min(candle.low, value);
                candle.close = value;
                candle.volume += 1;
            }

            return Array.from(buckets.values()).sort((a, b) => a.time - b.time);
        }

        function buildVolume(candles) {
            return candles.map((candle) => ({
                time: candle.time,
                value: candle.volume,
                color: candle.close >= candle.open ? 'rgba(34,197,94,0.55)' : 'rgba(244,63,94,0.55)',
            }));
        }

        function updateCurrentPrice(nextPrice) {
            const next = Number(nextPrice);
            if (!Number.isFinite(next)) {
                return;
            }

            const animationClass = next >= currentDisplayPrice ? 'price-tick-up' : 'price-tick-down';
            currentDisplayPrice = next;

            currentPriceEl.textContent = formatNum(next, 2);
            if (modalPriceEl) {
                modalPriceEl.textContent = formatNum(next, 2);
            }
            currentPriceEl.classList.remove('price-tick-up', 'price-tick-down');
            void currentPriceEl.offsetWidth;
            currentPriceEl.classList.add(animationClass);
        }

        function computeLivePositionPnl(trade, markPrice) {
            const entryRaw = trade.entry_price_value !== undefined && trade.entry_price_value !== null
                ? trade.entry_price_value
                : (trade.entry_price !== undefined && trade.entry_price !== null ? trade.entry_price : 0);
            const amountRaw = trade.amount_value !== undefined && trade.amount_value !== null
                ? trade.amount_value
                : (trade.amount !== undefined && trade.amount !== null ? trade.amount : 0);
            const entry = Number(entryRaw);
            const amount = Number(amountRaw);
            const mark = Number(markPrice);

            if (!Number.isFinite(entry) || entry <= 0 || !Number.isFinite(amount) || !Number.isFinite(mark)) {
                return 0;
            }

            const direction = String(trade.direction || '').toUpperCase();
            const ratio = direction === 'UP'
                ? (mark - entry) / entry
                : (entry - mark) / entry;

            return ratio * amount;
        }

        function generatePnlSparklineSvg(trade) {
            const series = baseSeries[trade.symbol] || [];
            if (!series.length) {
                return '<div class="position-sparkline-empty">No ticks</div>';
            }

            const createdTs = Number(trade.created_ts || 0);
            const scoped = createdTs > 0
                ? series.filter((point) => Number(point.time) >= createdTs)
                : series;

            const points = (scoped.length ? scoped : series).slice(-42);
            if (points.length < 2) {
                return '<div class="position-sparkline-empty">No ticks</div>';
            }

            const pnlSeries = points.map((point) => computeLivePositionPnl(trade, Number(point.value)));
            const min = Math.min(...pnlSeries);
            const max = Math.max(...pnlSeries);
            const range = Math.max(0.000001, max - min);

            const width = 132;
            const height = 42;
            const path = pnlSeries
                .map((value, idx) => {
                    const x = (idx / (pnlSeries.length - 1)) * width;
                    const y = height - ((value - min) / range) * height;
                    return `${x.toFixed(2)},${y.toFixed(2)}`;
                })
                .join(' ');

            const last = pnlSeries[pnlSeries.length - 1];
            const color = last >= 0 ? '#22c55e' : '#f43f5e';

            return `
                <svg viewBox="0 0 ${width} ${height}" class="position-sparkline" preserveAspectRatio="none" aria-hidden="true">
                    <polyline points="${path}" fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polyline>
                </svg>
            `;
        }

        function updateStats(candles) {
            if (!candles.length) {
                document.getElementById('stat-high').textContent = '-';
                document.getElementById('stat-low').textContent = '-';
                document.getElementById('stat-volume').textContent = '-';
                priceChangeEl.textContent = '0.00%';
                return;
            }

            const highs = candles.map((candle) => candle.high);
            const lows = candles.map((candle) => candle.low);
            const volume = candles.reduce((sum, candle) => sum + candle.volume, 0);

            document.getElementById('stat-high').textContent = formatNum(Math.max(...highs), 2);
            document.getElementById('stat-low').textContent = formatNum(Math.min(...lows), 2);
            document.getElementById('stat-volume').textContent = formatCompactNum(volume);

            if (candles.length >= 2) {
                const prev = candles[candles.length - 2].close;
                const last = candles[candles.length - 1].close;
                const change = prev === 0 ? 0 : ((last - prev) / prev) * 100;
                priceChangeEl.textContent = `${change >= 0 ? '+' : ''}${change.toFixed(2)}%`;
                priceChangeEl.className = `mt-1 text-sm font-semibold ${change >= 0 ? 'text-emerald-400' : 'text-rose-400'}`;
            }
        }

        function setOhlcLine(candle, volume = null) {
            if (!candle) {
                ohlcLineEl.textContent = 'Time: - Open: - High: - Low: - Close: - Volume: -';
                return;
            }

            const volValue = volume !== undefined && volume !== null ? volume : candle.volume;
            ohlcLineEl.textContent = `Time: ${formatTimeLabel(candle.time)} Open: ${formatNum(candle.open, 2)} High: ${formatNum(candle.high, 2)} Low: ${formatNum(candle.low, 2)} Close: ${formatNum(candle.close, 2)} Volume: ${formatCompactNum(volValue)}`;
        }

        function latestMarkPriceForTrade(trade) {
            const points = baseSeries[trade.symbol] || [];
            const latest = lastOf(points);

            if (latest && latest.value !== undefined && latest.value !== null) {
                return Number(latest.value);
            }

            const fallback = trade.entry_price_value !== undefined && trade.entry_price_value !== null
                ? trade.entry_price_value
                : trade.entry_price;

            return Number(fallback || 0);
        }

        function renderChart(symbol, fitContent = false) {
            const points = baseSeries[symbol] || [];
            const timeframeSeconds = timeframeMap[currentTimeframe];
            const candles = buildCandles(points, timeframeSeconds);

            if (!chartRuntime.available) {
                const fallbackLast = lastOf(candles);
                if (fallbackLast) {
                    updateCurrentPrice(fallbackLast.close);
                }

                updateStats(candles);
                setOhlcLine(fallbackLast);
                return;
            }

            chartRuntime.candleSeries.setData(candles.map((candle) => ({
                time: candle.time,
                open: candle.open,
                high: candle.high,
                low: candle.low,
                close: candle.close,
            })));
            chartRuntime.volumeSeries.setData(buildVolume(candles));

            const last = lastOf(candles);
            if (last) {
                updateCurrentPrice(last.close);
            }

            updateStats(candles);
            setOhlcLine(last);

            if (fitContent) {
                chartRuntime.chart.timeScale().fitContent();
            }
        }

        function setSymbolButtonsState(activeSymbol) {
            document.querySelectorAll('.symbol-btn').forEach((button) => {
                const selected = button.dataset.symbol === activeSymbol;
                button.classList.toggle('border-cyan-400/50', selected);
                button.classList.toggle('bg-cyan-500/20', selected);
                button.classList.toggle('text-cyan-200', selected);
                button.classList.toggle('border-slate-700', !selected);
                button.classList.toggle('bg-slate-950/70', !selected);
                button.classList.toggle('text-slate-300', !selected);
            });
        }

        function setTimeframeButtonsState(activeTimeframe) {
            document.querySelectorAll('.tf-btn').forEach((button) => {
                const selected = button.dataset.timeframe === activeTimeframe;
                button.classList.toggle('border-cyan-400/50', selected);
                button.classList.toggle('bg-cyan-500/20', selected);
                button.classList.toggle('text-cyan-200', selected);
                button.classList.toggle('border-slate-700', !selected);
                button.classList.toggle('bg-slate-950/70', !selected);
                button.classList.toggle('text-slate-300', !selected);
            });
        }

        function statusBadgeHtml(status) {
            const normalized = String(status || '').toUpperCase();
            if (normalized === 'WIN') {
                return `<span class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-0.5 text-xs text-emerald-200">WIN</span>`;
            }
            if (normalized === 'LOSE') {
                return `<span class="rounded-full border border-rose-500/40 bg-rose-500/10 px-2 py-0.5 text-xs text-rose-200">LOSE</span>`;
            }
            return `<span class="rounded-full border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-xs text-amber-200">PENDING</span>`;
        }

        function directionBadgeHtml(direction) {
            const normalized = String(direction || '').toUpperCase();
            if (normalized === 'UP') {
                return `<span class="rounded-full border border-lime-500/40 bg-lime-500/10 px-2 py-0.5 text-xs text-lime-200">UP</span>`;
            }
            return `<span class="rounded-full border border-rose-500/40 bg-rose-500/10 px-2 py-0.5 text-xs text-rose-200">DOWN</span>`;
        }

        function computeNetPnl(trade) {
            const status = String(trade.status || '').toUpperCase();
            const amount = Number(trade.amount_value || 0);
            const payout = Number(trade.payout_amount_value || 0);

            if (status === 'WIN') {
                return payout - amount;
            }
            if (status === 'LOSE') {
                return -amount;
            }
            return null;
        }

        function renderOpenPositions(trades) {
            const pending = (trades || [])
                .filter((trade) => String(trade.status || '').toUpperCase() === 'PENDING' && Number.isFinite(Number(trade.expiry_ts)))
                .sort((a, b) => Number(a.expiry_ts) - Number(b.expiry_ts));

            openCountMainEl.textContent = String(pending.length);
            openCountBadgeEl.textContent = String(pending.length);

            if (!pending.length) {
                openPositionsListEl.innerHTML = '<p class="text-slate-400">No active positions.</p>';
                return;
            }

            openPositionsListEl.innerHTML = pending.map((trade) => `
                <div class="rounded-xl border border-slate-800/80 bg-slate-950/70 p-3">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-slate-100">#${trade.id} ${trade.symbol}</p>
                        ${directionBadgeHtml(trade.direction)}
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-300">
                        <p>Amount: <span class="text-slate-100">${trade.amount} ${trade.wallet_currency}</span></p>
                        <p>Entry: <span class="text-slate-100">${trade.entry_price}</span></p>
                        <p>Expires: <span class="text-slate-100">${formatTimeLabel(trade.expiry_ts)}</span></p>
                        <p>Status: ${statusBadgeHtml(trade.status)}</p>
                    </div>
                    <div class="mt-2 rounded-lg border border-slate-800/80 bg-slate-900/50 px-2 py-2">
                        <div class="mb-1 flex items-center justify-between text-[11px] uppercase tracking-wide text-slate-400">
                            <span>Live P/L</span>
                            <span class="${computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)) >= 0 ? 'text-emerald-300' : 'text-rose-300'}">
                                ${computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)) >= 0 ? '+' : ''}${formatNum(computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)), 8)} ${trade.wallet_currency}
                            </span>
                        </div>
                        ${generatePnlSparklineSvg(trade)}
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-slate-400">Time Remaining</p>
                        <p class="countdown text-sm font-semibold text-amber-300" data-expiry="${Number(trade.expiry_ts)}" data-created="${Number(trade.created_ts || 0)}">--:--</p>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded bg-slate-800">
                        <div class="countdown-bar h-full bg-amber-400" data-expiry="${Number(trade.expiry_ts)}" data-created="${Number(trade.created_ts || 0)}" style="width: 100%;"></div>
                    </div>
                </div>
            `).join('');
        }

        function updateCountdownElements() {
            const nowSec = Math.floor(Date.now() / 1000);

            document.querySelectorAll('.countdown').forEach((el) => {
                const expiry = Number(el.dataset.expiry || 0);
                const remaining = expiry - nowSec;

                if (remaining <= 0) {
                    el.textContent = 'Settling...';
                    el.classList.remove('text-amber-300');
                    el.classList.add('text-slate-300');
                    return;
                }

                el.textContent = formatCountdown(remaining);
                el.classList.remove('text-slate-300');
                el.classList.add('text-amber-300');
            });

            document.querySelectorAll('.countdown-bar').forEach((bar) => {
                const expiry = Number(bar.dataset.expiry || 0);
                const created = Number(bar.dataset.created || 0);
                const total = Math.max(1, expiry - created);
                const remaining = Math.max(0, expiry - nowSec);
                const pct = Math.max(0, Math.min(100, (remaining / total) * 100));
                bar.style.width = `${pct}%`;
            });
        }

        function renderTradeSummary(trades) {
            const scopedTrades = trades.filter((trade) => String(trade.wallet_currency || 'USDT').toUpperCase() === String(currentTradeCurrency || 'USDT').toUpperCase());
            const pending = scopedTrades.filter((trade) => String(trade.status || '').toUpperCase() === 'PENDING');
            const settled = scopedTrades.filter((trade) => ['WIN', 'LOSE'].includes(String(trade.status || '').toUpperCase()));
            const wins = settled.filter((trade) => String(trade.status || '').toUpperCase() === 'WIN');
            const winRate = settled.length ? (wins.length / settled.length) * 100 : 0;

            const net = settled.reduce((sum, trade) => {
                const pnl = computeNetPnl(trade);
                return sum + (Number.isFinite(pnl) ? pnl : 0);
            }, 0);

            document.getElementById('summary-pending').textContent = String(pending.length);
            document.getElementById('summary-settled').textContent = String(settled.length);
            document.getElementById('summary-winrate').textContent = `${winRate.toFixed(1)}%`;

            const netEl = document.getElementById('summary-net');
            const netText = `${net >= 0 ? '+' : ''}${formatNum(net, 8)} ${currentTradeCurrency}`;
            netEl.textContent = netText;
            netEl.className = `mt-1 text-xl font-semibold ${net >= 0 ? 'text-emerald-300' : 'text-rose-300'}`;
        }

        function renderSettlementHealth(trades) {
            if (!settlementHealthEl) {
                return;
            }

            const nowSec = Math.floor(Date.now() / 1000);
            const graceSeconds = 12;
            const overduePending = (trades || []).filter((trade) => {
                const status = String(trade.status || '').toUpperCase();
                const expiry = Number(trade.expiry_ts || 0);

                return status === 'PENDING' && expiry > 0 && expiry + graceSeconds < nowSec;
            });

            if (!overduePending.length) {
                settlementHealthEl.classList.add('hidden');
                settlementHealthEl.textContent = '';
                return;
            }

            settlementHealthEl.classList.remove('hidden');
            settlementHealthEl.textContent = `${overduePending.length} trade(s) passed expiry but are still pending. Scheduler may be offline or delayed.`;
        }

        function renderRecentTrades(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                recentTradesBodyEl.innerHTML = '<tr><td colspan="10" class="px-3 py-3 text-slate-400">No trades yet.</td></tr>';
                return;
            }

            recentTradesBodyEl.innerHTML = rows.map((trade) => {
                const pnl = computeNetPnl(trade);
                const pnlText = pnl === null ? '-' : `${pnl >= 0 ? '+' : ''}${formatNum(pnl, 8)} ${trade.wallet_currency}`;
                const pnlClass = pnl === null ? 'text-slate-400' : pnl >= 0 ? 'text-emerald-300' : 'text-rose-300';

                return `
                    <tr>
                        <td>#${trade.id}</td>
                        <td>${trade.symbol}</td>
                        <td>${trade.wallet_currency}</td>
                        <td>${directionBadgeHtml(trade.direction)}</td>
                        <td>${trade.amount} ${trade.wallet_currency}</td>
                        <td>${trade.entry_price}</td>
                        <td>${trade.close_price}</td>
                        <td>${formatTimeLabel(trade.expiry_ts)}</td>
                        <td class="${pnlClass}">${pnlText}</td>
                        <td>${statusBadgeHtml(trade.status)}</td>
                    </tr>
                `;
            }).join('');
        }

        async function refreshSeries(symbol) {
            const response = await fetch(`${priceApiEndpoint}?symbol=${encodeURIComponent(symbol)}&limit=${chartHistoryLimit}`);
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            baseSeries[symbol] = data.series || [];
            marketStatusBySymbol[symbol] = {
                symbol,
                price: data.price ?? null,
                tick_time: data.tick_time ?? null,
                age_seconds: data.age_seconds ?? null,
                age_label: data.age_label ?? 'No market tick recorded yet',
                is_fresh: Boolean(data.is_fresh),
                max_age_seconds: data.max_age_seconds ?? null,
            };

            if (symbol === currentSymbol) {
                updatePriceFreshnessDisplay();
                renderChart(symbol, false);
            }
        }

        async function refreshRecentTrades() {
            const response = await fetch(recentTradesEndpoint, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            latestTrades = data.trades || [];
            renderOpenPositions(latestTrades);
            renderRecentTrades(latestTrades);
            renderTradeSummary(latestTrades);
            renderSettlementHealth(latestTrades);
            updateCountdownElements();
        }

        window.addEventListener('resize', () => {
            if (chartRuntime.available && chartRuntime.chart && chartContainer) {
                chartRuntime.chart.applyOptions({ width: chartContainer.clientWidth });
            }
        });

        if (chartRuntime.available && chartRuntime.chart) {
            chartRuntime.chart.subscribeCrosshairMove((param) => {
                if (!param || !param.time) {
                    return;
                }

                const candleData = param.seriesData.get(chartRuntime.candleSeries);
                if (!candleData) {
                    return;
                }

                const candle = {
                    time: normalizeChartTime(param.time),
                    open: candleData.open,
                    high: candleData.high,
                    low: candleData.low,
                    close: candleData.close,
                    volume: null,
                };

                const volData = param.seriesData.get(chartRuntime.volumeSeries);
                setOhlcLine(candle, volData ? volData.value : null);
            });
        }

        document.querySelectorAll('.symbol-btn').forEach((button) => {
            button.addEventListener('click', () => {
                currentSymbol = button.dataset.symbol;
                primarySymbolInput.value = currentSymbol;
                if (modalSymbolEl) {
                    modalSymbolEl.textContent = currentSymbol;
                }
                setSymbolButtonsState(currentSymbol);
                updatePriceFreshnessDisplay();
                renderChart(currentSymbol, true);
            });
        });

        function applyDirection(direction) {
            if (!tradeDirectionInput) {
                return;
            }

            const normalized = String(direction || 'UP').toUpperCase() === 'DOWN' ? 'DOWN' : 'UP';
            tradeDirectionInput.value = normalized;

            if (modalDirectionHintEl) {
                modalDirectionHintEl.textContent = normalized === 'UP' ? 'Direction: Buy Up' : 'Direction: Buy Down';
            }

            if (modalSubmitEl) {
                modalSubmitEl.textContent = normalized === 'UP' ? 'Place Buy Up' : 'Place Buy Down';
                modalSubmitEl.classList.toggle('btn-up', normalized === 'UP');
                modalSubmitEl.classList.toggle('btn-down', normalized === 'DOWN');
                modalSubmitEl.classList.toggle('btn-primary', false);
            }

            updateTradeSubmitAvailability();
        }

        function openTradeModal(direction) {
            if (!tradeModalBackdropEl) {
                return;
            }

            if (direction) {
                applyDirection(direction);
            }

            tradeModalBackdropEl.classList.remove('hidden');
            if (tradeLauncherEl) {
                tradeLauncherEl.setAttribute('aria-expanded', 'true');
            }
        }

        function closeTradeModal() {
            if (!tradeModalBackdropEl) {
                return;
            }

            tradeModalBackdropEl.classList.add('hidden');
            if (tradeLauncherEl) {
                tradeLauncherEl.setAttribute('aria-expanded', 'false');
            }
        }

        function submitPrimaryTrade(direction) {
            const form = tradeFormEl || (primaryAmountInput ? primaryAmountInput.form : null);
            if (!form) {
                return;
            }

            applyDirection(direction);
            form.submit();
        }

        if (tradeLauncherEl) {
            tradeLauncherEl.addEventListener('click', () => openTradeModal());
        }
        if (openTradeModalEl) {
            openTradeModalEl.addEventListener('click', () => openTradeModal());
        }
        if (openModalUpEl) {
            openModalUpEl.addEventListener('click', () => openTradeModal('UP'));
        }
        if (openModalDownEl) {
            openModalDownEl.addEventListener('click', () => openTradeModal('DOWN'));
        }
        if (openModalUpMobileEl) {
            openModalUpMobileEl.addEventListener('click', () => openTradeModal('UP'));
        }
        if (openModalDownMobileEl) {
            openModalDownMobileEl.addEventListener('click', () => openTradeModal('DOWN'));
        }
        if (closeTradeModalEl) {
            closeTradeModalEl.addEventListener('click', closeTradeModal);
        }
        if (tradeModalBackdropEl) {
            tradeModalBackdropEl.addEventListener('click', (event) => {
                if (event.target === tradeModalBackdropEl) {
                    closeTradeModal();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeTradeModal();
            }
        });

        if (tradeWalletCurrencyInput) {
            tradeWalletCurrencyInput.addEventListener('change', () => {
                updateSelectedWalletDisplay(tradeWalletCurrencyInput.value);
                renderTradeSummary(latestTrades);
            });
        }

        if (tradeFormEl) {
            tradeFormEl.addEventListener('submit', (event) => {
                const status = symbolStatus(currentSymbol);

                if (!status?.is_fresh || !modalSubmitEl) {
                    event.preventDefault();
                    updatePriceFreshnessDisplay();

                    return;
                }

                modalSubmitEl.dataset.submitting = 'true';
                modalSubmitEl.disabled = true;
                modalSubmitEl.classList.add('opacity-70', 'cursor-not-allowed');
                modalSubmitEl.textContent = 'Submitting...';
            });
        }

        document.querySelectorAll('.tf-btn').forEach((button) => {
            button.addEventListener('click', () => {
                currentTimeframe = button.dataset.timeframe;
                setTimeframeButtonsState(currentTimeframe);
                renderChart(currentSymbol, true);
            });
        });

        setSymbolButtonsState(currentSymbol);
        setTimeframeButtonsState(currentTimeframe);
        if (modalSymbolEl) {
            modalSymbolEl.textContent = currentSymbol;
        }
        updateSelectedWalletDisplay(currentTradeCurrency);
        applyDirection('UP');
        updatePriceFreshnessDisplay();
        renderChart(currentSymbol, true);

        renderOpenPositions(latestTrades);
        renderRecentTrades(latestTrades);
        renderTradeSummary(latestTrades);
        renderSettlementHealth(latestTrades);
        updateCountdownElements();

        const updatePricesLoop = async () => {
            await refreshSeries(currentSymbol);
        };

        const updateTradesLoop = async () => {
            await refreshRecentTrades();
        };

        let pricesPollingHandle = setInterval(updatePricesLoop, pricesPollIntervalMs);
        let tradesPollingHandle = setInterval(updateTradesLoop, tradesPollIntervalMs);
        let countdownHandle = setInterval(updateCountdownElements, 1000);

        Promise.all([
            updatePricesLoop(),
            updateTradesLoop(),
        ]);

        if (window.Echo) {
            window.Echo.channel('prices').listen('.tick.updated', (event) => {
                if (!event || !event.symbol || event.price == null || event.timestamp == null) {
                    return;
                }

                marketStatusBySymbol[event.symbol] = {
                    symbol: event.symbol,
                    price: event.price,
                    tick_time: new Date(Number(event.timestamp) * 1000).toISOString(),
                    age_seconds: 0,
                    age_label: '0s ago',
                    is_fresh: true,
                };

                if (!baseSeries[event.symbol]) {
                    baseSeries[event.symbol] = [];
                }

                baseSeries[event.symbol].push({
                    time: Number(event.timestamp),
                    value: Number(event.price),
                });

                if (baseSeries[event.symbol].length > 4000) {
                    baseSeries[event.symbol].shift();
                }

                if (event.symbol === currentSymbol) {
                    updatePriceFreshnessDisplay();
                    renderChart(currentSymbol, false);
                }

                renderOpenPositions(latestTrades);
                renderSettlementHealth(latestTrades);
                updateCountdownElements();
            });
        }

        window.addEventListener('beforeunload', () => {
            if (pricesPollingHandle) {
                clearInterval(pricesPollingHandle);
                pricesPollingHandle = null;
            }

            if (tradesPollingHandle) {
                clearInterval(tradesPollingHandle);
                tradesPollingHandle = null;
            }

            if (countdownHandle) {
                clearInterval(countdownHandle);
                countdownHandle = null;
            }
        });
    </script>
</x-layouts.app>
