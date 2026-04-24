<x-layouts.app :title="'Trade'">
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
                </div>
                <div class="card-muted">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Wallet (Available)</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-100">{{ number_format((float) optional($wallet)->balance, 2) }} USDT</p>
                    <p class="mt-1 text-xs text-slate-400">Locked: {{ number_format((float) optional($wallet)->locked_balance, 2) }} USDT</p>
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

            <div class="overflow-hidden rounded-2xl border border-slate-800/80 bg-slate-950/80">
                <div id="chart" class="h-[500px] w-full"></div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="glass-panel">
                <h2 class="mb-1 text-base font-semibold">Place Trade</h2>
                <p class="mb-3 text-xs text-slate-400">Fast execution with expiry-aware settlement.</p>
                <form method="POST" action="{{ route('trade.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="symbol" id="trade-symbol" value="BTCUSDT">

                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Amount (USDT)</label>
                        <input id="trade-amount" name="amount" type="number" step="0.01" min="1" required class="input-dark" placeholder="e.g. 50">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Duration</label>
                        <select id="trade-duration" name="duration" class="input-dark">
                            <option value="1">1 minute</option>
                            <option value="5">5 minutes</option>
                            <option value="15">15 minutes</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button name="direction" value="UP" class="btn-up py-2.5">Buy Up</button>
                        <button name="direction" value="DOWN" class="btn-down py-2.5">Buy Down</button>
                    </div>
                </form>
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
                <p id="summary-net" class="mt-1 text-xl font-semibold text-slate-100">0.00 USDT</p>
            </div>
        </div>

        <h2 class="mb-3 text-base font-semibold">Recent Trades</h2>
        <div class="overflow-auto">
            <table class="table-dark">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
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
                        <td colspan="9" class="px-3 py-3 text-slate-400">Loading trades...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <button id="ticket-fab" type="button" class="floating-ticket-fab" aria-controls="floating-ticket" aria-expanded="false">
        Quick Trade
    </button>

    <aside id="floating-ticket" class="floating-ticket-panel is-hidden" aria-live="polite">
        <div class="mb-2 flex items-center justify-between">
            <p class="text-xs uppercase tracking-[0.18em] text-cyan-300">Quick Ticket</p>
            <button id="ticket-close" type="button" class="text-xs text-slate-400 hover:text-slate-200">Hide</button>
        </div>

        <div class="space-y-2 text-sm">
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Symbol</p>
                <p id="ticket-symbol" class="font-semibold text-slate-100">BTCUSDT</p>
            </div>
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/70 px-3 py-2">
                <p class="text-[11px] uppercase tracking-wide text-slate-400">Mark Price</p>
                <p id="ticket-price" class="font-semibold text-cyan-300">{{ number_format((float) $btcPrice, 2) }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs uppercase tracking-wide text-slate-400">Amount (USDT)</label>
                <input id="ticket-amount" type="number" step="0.01" min="1" class="input-dark" value="10">
            </div>

            <div>
                <label class="mb-1 block text-xs uppercase tracking-wide text-slate-400">Duration</label>
                <select id="ticket-duration" class="input-dark">
                    <option value="1">1 minute</option>
                    <option value="5">5 minutes</option>
                    <option value="15">15 minutes</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <button id="ticket-buy-up" type="button" class="btn-up py-2">Buy Up</button>
                <button id="ticket-buy-down" type="button" class="btn-down py-2">Buy Down</button>
            </div>
        </div>
    </aside>

    <script>
        const baseSeries = {
            BTCUSDT: @json($btcSeries),
            ETHUSDT: @json($ethSeries),
        };

        const initialRecentTrades = @json($recentTradesPayload);
        const priceApiEndpoint = @json(url('/api/prices/latest'));
        const recentTradesEndpoint = @json(route('trade.recent'));
        const chartHistoryLimit = 3600;

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
        let currentDisplayPrice = Number({{ (float) $btcPrice }});
        let latestTrades = Array.isArray(initialRecentTrades) ? initialRecentTrades : [];

        const chartContainer = document.getElementById('chart');
        const currentPriceEl = document.getElementById('current-price');
        const priceChangeEl = document.getElementById('price-change');
        const ohlcLineEl = document.getElementById('ohlc-line');
        const recentTradesBodyEl = document.getElementById('recent-trades-body');
        const openPositionsListEl = document.getElementById('open-positions-list');
        const openCountMainEl = document.getElementById('open-count-main');
        const openCountBadgeEl = document.getElementById('open-count-badge');
        const settlementHealthEl = document.getElementById('settlement-health');
        const primaryAmountInput = document.getElementById('trade-amount');
        const primaryDurationInput = document.getElementById('trade-duration');
        const primarySymbolInput = document.getElementById('trade-symbol');

        const ticketFabEl = document.getElementById('ticket-fab');
        const floatingTicketEl = document.getElementById('floating-ticket');
        const ticketCloseEl = document.getElementById('ticket-close');
        const ticketSymbolEl = document.getElementById('ticket-symbol');
        const ticketPriceEl = document.getElementById('ticket-price');
        const ticketAmountInput = document.getElementById('ticket-amount');
        const ticketDurationInput = document.getElementById('ticket-duration');
        const ticketBuyUpBtn = document.getElementById('ticket-buy-up');
        const ticketBuyDownBtn = document.getElementById('ticket-buy-down');

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
            ticketPriceEl.textContent = formatNum(next, 2);
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
                        <p>Amount: <span class="text-slate-100">${trade.amount} USDT</span></p>
                        <p>Entry: <span class="text-slate-100">${trade.entry_price}</span></p>
                        <p>Expires: <span class="text-slate-100">${formatTimeLabel(trade.expiry_ts)}</span></p>
                        <p>Status: ${statusBadgeHtml(trade.status)}</p>
                    </div>
                    <div class="mt-2 rounded-lg border border-slate-800/80 bg-slate-900/50 px-2 py-2">
                        <div class="mb-1 flex items-center justify-between text-[11px] uppercase tracking-wide text-slate-400">
                            <span>Live P/L</span>
                            <span class="${computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)) >= 0 ? 'text-emerald-300' : 'text-rose-300'}">
                                ${computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)) >= 0 ? '+' : ''}${formatNum(computeLivePositionPnl(trade, latestMarkPriceForTrade(trade)), 2)}
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
            const pending = trades.filter((trade) => String(trade.status || '').toUpperCase() === 'PENDING');
            const settled = trades.filter((trade) => ['WIN', 'LOSE'].includes(String(trade.status || '').toUpperCase()));
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
            const netText = `${net >= 0 ? '+' : ''}${formatNum(net, 2)} USDT`;
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
                recentTradesBodyEl.innerHTML = '<tr><td colspan="9" class="px-3 py-3 text-slate-400">No trades yet.</td></tr>';
                return;
            }

            recentTradesBodyEl.innerHTML = rows.map((trade) => {
                const pnl = computeNetPnl(trade);
                const pnlText = pnl === null ? '-' : `${pnl >= 0 ? '+' : ''}${formatNum(pnl, 2)}`;
                const pnlClass = pnl === null ? 'text-slate-400' : pnl >= 0 ? 'text-emerald-300' : 'text-rose-300';

                return `
                    <tr>
                        <td>#${trade.id}</td>
                        <td>${trade.symbol}</td>
                        <td>${directionBadgeHtml(trade.direction)}</td>
                        <td>${trade.amount}</td>
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

            if (symbol === currentSymbol) {
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
                ticketSymbolEl.textContent = currentSymbol;
                setSymbolButtonsState(currentSymbol);
                renderChart(currentSymbol, true);
            });
        });

        function submitPrimaryTrade(direction) {
            const form = primaryAmountInput ? primaryAmountInput.form : null;
            if (!form) {
                return;
            }

            const directionInput = document.createElement('input');
            directionInput.type = 'hidden';
            directionInput.name = 'direction';
            directionInput.value = direction;
            form.appendChild(directionInput);
            form.submit();
        }

        function syncPrimaryFromTicket() {
            if (primaryAmountInput && ticketAmountInput) {
                primaryAmountInput.value = ticketAmountInput.value;
            }

            if (primaryDurationInput && ticketDurationInput) {
                primaryDurationInput.value = ticketDurationInput.value;
            }

            if (primarySymbolInput) {
                primarySymbolInput.value = currentSymbol;
            }
        }

        function syncTicketFromPrimary() {
            if (primaryAmountInput && ticketAmountInput) {
                ticketAmountInput.value = primaryAmountInput.value || ticketAmountInput.value;
            }

            if (primaryDurationInput && ticketDurationInput) {
                ticketDurationInput.value = primaryDurationInput.value || ticketDurationInput.value;
            }
        }

        if (ticketBuyUpBtn) {
            ticketBuyUpBtn.addEventListener('click', () => {
                syncPrimaryFromTicket();
                submitPrimaryTrade('UP');
            });
        }

        if (ticketBuyDownBtn) {
            ticketBuyDownBtn.addEventListener('click', () => {
                syncPrimaryFromTicket();
                submitPrimaryTrade('DOWN');
            });
        }

        if (primaryAmountInput) {
            primaryAmountInput.addEventListener('input', syncTicketFromPrimary);
        }
        if (primaryDurationInput) {
            primaryDurationInput.addEventListener('change', syncTicketFromPrimary);
        }
        if (ticketAmountInput) {
            ticketAmountInput.addEventListener('input', syncPrimaryFromTicket);
        }
        if (ticketDurationInput) {
            ticketDurationInput.addEventListener('change', syncPrimaryFromTicket);
        }

        if (ticketFabEl && floatingTicketEl) {
            ticketFabEl.addEventListener('click', () => {
                const willOpen = floatingTicketEl.classList.contains('is-hidden');
                floatingTicketEl.classList.toggle('is-hidden', !willOpen);
                ticketFabEl.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        if (ticketCloseEl && floatingTicketEl && ticketFabEl) {
            ticketCloseEl.addEventListener('click', () => {
                floatingTicketEl.classList.add('is-hidden');
                ticketFabEl.setAttribute('aria-expanded', 'false');
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
        ticketSymbolEl.textContent = currentSymbol;
        syncTicketFromPrimary();
        renderChart(currentSymbol, true);

        renderOpenPositions(latestTrades);
        renderRecentTrades(latestTrades);
        renderTradeSummary(latestTrades);
        renderSettlementHealth(latestTrades);
        updateCountdownElements();

        const updateLoop = async () => {
            await Promise.all([
                refreshSeries(currentSymbol),
                refreshRecentTrades(),
            ]);
        };

        let pollingHandle = setInterval(updateLoop, 3000);
        let countdownHandle = setInterval(updateCountdownElements, 1000);
        updateLoop();

        if (window.Echo) {
            window.Echo.channel('prices').listen('.tick.updated', (event) => {
                if (!event || !event.symbol || event.price == null || event.timestamp == null) {
                    return;
                }

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
                    renderChart(currentSymbol, false);
                }

                renderOpenPositions(latestTrades);
                renderSettlementHealth(latestTrades);
                updateCountdownElements();
            });
        }

        window.addEventListener('beforeunload', () => {
            if (pollingHandle) {
                clearInterval(pollingHandle);
                pollingHandle = null;
            }

            if (countdownHandle) {
                clearInterval(countdownHandle);
                countdownHandle = null;
            }
        });
    </script>
</x-layouts.app>
