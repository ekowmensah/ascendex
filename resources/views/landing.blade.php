<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png?v=2">
    <link rel="apple-touch-icon" href="/favicon.png?v=2">
    <title>Trans Market Group | Live Crypto Market</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-100">
<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -left-24 top-16 h-72 w-72 rounded-full bg-cyan-500/15 blur-3xl"></div>
    <div class="absolute -right-24 top-28 h-80 w-80 rounded-full bg-violet-500/15 blur-3xl"></div>
    <div class="absolute bottom-8 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-500/10 blur-3xl"></div>
</div>

@php
    $displayCurrencies = ['USDT', 'BTC', 'ETH'];
    $freshFeeds = collect($symbolStatuses)->filter(fn (array $status): bool => (bool) ($status['is_fresh'] ?? false))->count();
    $totalFeeds = count($symbolStatuses);
@endphp

<header class="app-shell py-5">
    <div class="glass-panel border-slate-700/70">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2">
                <img src="/favicon.png?v=2" alt="TransMarket" class="h-9 w-9 rounded-md bg-slate-900/60 p-1">
                <span class="text-base font-semibold tracking-wide sm:text-lg">Trans Market</span>
            </a>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <a class="pill-link" href="#markets">Markets</a>
                <a class="pill-link" href="#wallet-rates">Wallet Rates</a>
                @auth
                    <a class="pill-link" href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="btn-primary" href="{{ route('trade.index') }}">Open Trade Desk</a>
                @else
                    <a class="pill-link" href="{{ route('login') }}">Login</a>
                    <a class="btn-primary" href="{{ route('register.form') }}">Create Account</a>
                @endauth
            </nav>
        </div>
    </div>
</header>

<main class="app-shell pb-16">
    <section class="glass-panel border-slate-700/70">
        <div class="grid gap-8 xl:grid-cols-[1.05fr,0.95fr] xl:items-center">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-cyan-300">Real-Time Crypto Dashboard</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                    Trade faster with live market visibility and secure blockchain rails.
                </h1>
                <p class="mt-4 max-w-2xl text-slate-300">
                    Trans Market Group gives you a clear, desktop-ready trading surface with mobile responsiveness,
                    real-time price updates, and transparent wallet conversion rates.
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="#markets" class="btn-primary">View Live Markets</a>
                    @auth
                        <a href="{{ route('trade.index') }}" class="pill-link">Go to Trading</a>
                    @else
                        <a href="{{ route('register.form') }}" class="pill-link">Start an Account</a>
                    @endauth
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="card-muted">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Pairs Tracked</p>
                        <p class="mt-1 text-xl font-semibold text-cyan-300">{{ count($coinRows) }}</p>
                    </div>
                    <div class="card-muted">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Market Feed Health</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-300">{{ $freshFeeds }}/{{ $totalFeeds }} fresh</p>
                    </div>
                    <div class="card-muted">
                        <p class="text-xs uppercase tracking-wide text-slate-400">1 USDT in {{ $localCurrency }}</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">{{ number_format($ghsPerUsdt, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
               <!-- <figure class="hero-visual-card overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-950/70 shadow-xl shadow-cyan-950/30 sm:col-span-2" style="min-height: 10.5rem;">
                    <svg viewBox="0 0 600 280" class="hero-visual-svg" role="img" aria-label="Blockchain network map" style="display:block;width:100%;height:100%;">
                        <defs>
                            <linearGradient id="hero-network-line" x1="40" y1="30" x2="560" y2="260" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#22d3ee"></stop>
                                <stop offset="1" stop-color="#8b5cf6"></stop>
                            </linearGradient>
                            <radialGradient id="hero-network-glow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(330 120) rotate(90) scale(130 230)">
                                <stop stop-color="#22d3ee" stop-opacity="0.35"></stop>
                                <stop offset="1" stop-color="#22d3ee" stop-opacity="0"></stop>
                            </radialGradient>
                        </defs>
                        <rect x="0" y="0" width="600" height="280" fill="#0b1220"></rect>
                        <ellipse cx="330" cy="120" rx="230" ry="130" fill="url(#hero-network-glow)"></ellipse>
                        <g stroke="url(#hero-network-line)" stroke-width="3">
                            <path d="M48 64L165 48L282 90L395 70L533 122"></path>
                            <path d="M74 152L194 120L307 168L412 140L530 188"></path>
                            <path d="M108 232L214 190L323 224L426 198L542 236"></path>
                            <path d="M165 48L194 120L214 190"></path>
                            <path d="M282 90L307 168L323 224"></path>
                            <path d="M395 70L412 140L426 198"></path>
                        </g>
                        <g fill="#0b1220" stroke="#67e8f9" stroke-width="4">
                            <circle cx="48" cy="64" r="10"></circle>
                            <circle cx="165" cy="48" r="10"></circle>
                            <circle cx="282" cy="90" r="11"></circle>
                            <circle cx="395" cy="70" r="11"></circle>
                            <circle cx="533" cy="122" r="10"></circle>
                            <circle cx="74" cy="152" r="10"></circle>
                            <circle cx="194" cy="120" r="11"></circle>
                            <circle cx="307" cy="168" r="11"></circle>
                            <circle cx="412" cy="140" r="11"></circle>
                            <circle cx="530" cy="188" r="10"></circle>
                            <circle cx="108" cy="232" r="10"></circle>
                            <circle cx="214" cy="190" r="11"></circle>
                            <circle cx="323" cy="224" r="11"></circle>
                            <circle cx="426" cy="198" r="11"></circle>
                            <circle cx="542" cy="236" r="10"></circle>
                        </g>
                    </svg>
                </figure> -->
                <figure class="hero-visual-card overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-950/70 shadow-xl shadow-violet-950/25 sm:col-span-2" style="min-height: 10.5rem;">
                    <svg viewBox="0 0 600 280" class="hero-visual-svg" role="img" aria-label="Blockchain security lock" style="display:block;width:100%;height:100%;">
                        <defs>
                            <linearGradient id="hero-lock-line" x1="180" y1="48" x2="420" y2="240" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#22d3ee"></stop>
                                <stop offset="1" stop-color="#8b5cf6"></stop>
                            </linearGradient>
                        </defs>
                        <rect x="0" y="0" width="600" height="280" fill="#0a1020"></rect>
                        <g opacity="0.7" stroke="#334155" stroke-width="2">
                            <path d="M40 68H560"></path>
                            <path d="M40 120H560"></path>
                            <path d="M40 172H560"></path>
                            <path d="M40 224H560"></path>
                            <path d="M118 34V248"></path>
                            <path d="M212 34V248"></path>
                            <path d="M300 34V248"></path>
                            <path d="M388 34V248"></path>
                            <path d="M482 34V248"></path>
                        </g>
                        <g stroke="url(#hero-lock-line)" stroke-width="5" stroke-linejoin="round">
                            <path d="M300 54L382 98V184L300 228L218 184V98L300 54Z"></path>
                            <path d="M300 114L340 137V184L300 206L260 184V137L300 114Z"></path>
                            <path d="M300 54V114"></path>
                            <path d="M218 98L260 137"></path>
                            <path d="M382 98L340 137"></path>
                        </g>
                        <rect x="258" y="186" width="84" height="60" rx="12" fill="#020617" stroke="#22d3ee" stroke-width="4"></rect>
                        <path d="M276 186V172C276 158 286 148 300 148C314 148 324 158 324 172V186" stroke="#22d3ee" stroke-width="5" stroke-linecap="round"></path>
                        <circle cx="300" cy="214" r="7" fill="#22d3ee"></circle>
                    </svg>
                </figure>
            </div>
        </div>
    </section>

    <section id="wallet-rates" class="mt-8">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-cyan-300">Wallet Conversion</p>
                <h2 class="mt-1 text-2xl font-semibold">Base Wallet Rates</h2>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($displayCurrencies as $currency)
                @php
                    $option = $walletOptions[$currency] ?? null;
                    $symbol = $option['market_symbol'] ?? null;
                    $status = $symbol ? ($symbolStatuses[$symbol] ?? null) : null;
                    $isFresh = $currency === 'USDT' ? true : (bool) ($status['is_fresh'] ?? false);
                    $ghsRate = is_numeric($option['ghs_per_wallet_unit'] ?? null)
                        ? number_format((float) $option['ghs_per_wallet_unit'], 2)
                        : '--';
                    $priceInUsdt = is_numeric($option['price_in_usdt'] ?? null)
                        ? number_format((float) $option['price_in_usdt'], $currency === 'USDT' ? 4 : 2)
                        : '--';
                    $freshnessLabel = $currency === 'USDT'
                        ? 'Admin-managed base rate'
                        : (string) ($status['age_label'] ?? 'No market tick recorded yet');
                @endphp

                <article class="glass-panel border-slate-700/70">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-slate-400">{{ $currency }}</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-100">{{ $ghsRate }} {{ $localCurrency }}</p>
                        </div>
                        <span class="rounded-full border px-2 py-0.5 text-[11px] {{ $isFresh ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200' : 'border-amber-500/40 bg-amber-500/10 text-amber-200' }}">
                            {{ $isFresh ? 'Live' : 'Stale' }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-1 text-sm text-slate-300">
                        <p>
                            <span class="text-slate-400">Price in USDT:</span>
                            <span>{{ $priceInUsdt }}</span>
                        </p>
                        <p class="text-xs text-slate-400">{{ $freshnessLabel }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="markets" class="mt-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-cyan-300">Live Coin Rates</p>
                <h2 class="mt-1 text-2xl font-semibold">Market Rate Board</h2>
            </div>
            <span id="rates-last-updated" class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1 text-xs text-slate-300">
                Initial snapshot
            </span>
        </div>

        <div class="glass-panel overflow-hidden border-slate-700/70 p-0">
            <div class="overflow-x-auto">
                <table class="table-dark" aria-label="Crypto market rates">
                    <thead>
                    <tr>
                        <th>Pair</th>
                        <th>Price (USDT)</th>
                        <th>Price ({{ $localCurrency }})</th>
                        <th>24h Change</th>
                        <th>Trend</th>
                    </tr>
                    </thead>
                    <tbody id="market-rate-table-body">
                    @foreach($coinRows as $row)
                        <tr data-symbol="{{ $row['symbol'] }}" data-price="{{ $row['price'] ?? '' }}" data-change="{{ $row['change_percent'] ?? '' }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-xs font-bold text-slate-200">
                                        {{ substr((string) $row['base'], 0, 1) }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-100">{{ $row['base'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['base'] }}/{{ $row['quote'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="font-semibold text-slate-100" data-role="price">{{ $row['price_label'] }}</td>
                            <td data-role="local-price">{{ $row['local_price_label'] }}</td>
                            <td>
                                <span data-role="change" class="font-semibold {{ $row['is_positive'] ? 'text-emerald-300' : 'text-rose-300' }}">
                                    {{ $row['change_label'] }}
                                </span>
                            </td>
                            <td>
                                <svg
                                    class="rate-sparkline {{ $row['is_positive'] ? 'rate-sparkline-up' : 'rate-sparkline-down' }}"
                                    data-role="sparkline"
                                    viewBox="0 0 110 34"
                                    preserveAspectRatio="none"
                                    aria-label="{{ $row['base'] }} trend chart"
                                    style="display:block;width:6.9rem;height:2rem;"
                                >
                                    <path class="rate-sparkline-area" data-role="sparkline-area" d=""></path>
                                    <path class="rate-sparkline-line" data-role="sparkline-line" d=""></path>
                                </svg>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <div class="glass-panel border-slate-700/70">
            <h3 class="text-xl font-semibold">About Trans Market</h3>
            <p class="mt-3 text-slate-300">
                Trans Market Group provides a secure and efficient digital asset trading experience with transparent
                pricing, quick execution, and market visibility for both new and active traders.
            </p>
        </div>
    </section>
</main>

<script>
    (() => {
        const marketRatesApiEndpoint = @json($marketRatesApiEndpoint);
        const fallbackGhsPerUsdt = Number.parseFloat(@json($ghsPerUsdt));
        const tableBody = document.getElementById('market-rate-table-body');
        const lastUpdatedEl = document.getElementById('rates-last-updated');

        if (!tableBody) {
            return;
        }

        const rowBySymbol = new Map();
        const SPARKLINE_POINT_LIMIT = 24;
        const SPARKLINE_WIDTH = 110;
        const SPARKLINE_HEIGHT = 34;
        const SPARKLINE_PADDING = 2;

        Array.from(tableBody.querySelectorAll('tr[data-symbol]')).forEach((row) => {
            const symbol = row.getAttribute('data-symbol');
            if (!symbol) {
                return;
            }

            const initialPrice = Number.parseFloat(row.getAttribute('data-price') ?? 'NaN');
            const initialChange = Number.parseFloat(row.getAttribute('data-change') ?? 'NaN');
            const history = buildSeedSeries(initialPrice, initialChange);
            rowBySymbol.set(symbol, {
                row,
                previousPrice: Number.isFinite(initialPrice) ? initialPrice : Number.NaN,
                history,
            });

            renderSparkline(row, history, Number.isFinite(initialChange) ? initialChange >= 0 : true);
        });

        function resolveGhsRate(payload) {
            const payloadRate = Number.parseFloat(payload?.ghs_per_usdt ?? 'NaN');

            if (Number.isFinite(payloadRate) && payloadRate > 0) {
                return payloadRate;
            }

            return Number.isFinite(fallbackGhsPerUsdt) && fallbackGhsPerUsdt > 0
                ? fallbackGhsPerUsdt
                : Number.NaN;
        }

        function formatPrice(value) {
            if (!Number.isFinite(value)) {
                return '--';
            }

            if (value >= 1000) {
                return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            if (value >= 1) {
                return value.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
            }

            if (value >= 0.01) {
                return value.toLocaleString(undefined, { minimumFractionDigits: 6, maximumFractionDigits: 6 });
            }

            return value.toLocaleString(undefined, { minimumFractionDigits: 8, maximumFractionDigits: 8 });
        }

        function formatLocal(value) {
            if (!Number.isFinite(value)) {
                return '--';
            }

            return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatChange(value) {
            if (!Number.isFinite(value)) {
                return '--';
            }

            return `${value >= 0 ? '+' : ''}${value.toFixed(2)}%`;
        }

        function pulsePrice(priceEl, direction) {
            if (!priceEl || !direction) {
                return;
            }

            priceEl.classList.remove('price-tick-up', 'price-tick-down');
            void priceEl.offsetWidth;
            priceEl.classList.add(direction === 'up' ? 'price-tick-up' : 'price-tick-down');
        }

        function buildSeedSeries(price, changePercent) {
            if (!Number.isFinite(price) || price <= 0) {
                return [];
            }

            const trend = Number.isFinite(changePercent)
                ? Math.max(Math.min(changePercent / 100, 0.14), -0.14)
                : 0;
            const series = [];

            for (let index = 0; index < SPARKLINE_POINT_LIMIT; index += 1) {
                const progress = index / (SPARKLINE_POINT_LIMIT - 1);
                const drift = price * trend * (progress - 0.5) * 0.38;
                const wave = Math.sin((index + 1) * 0.85) * price * 0.006;
                const value = Math.max(price + drift + wave, price * 0.35);
                series.push(value);
            }

            series[series.length - 1] = price;

            return series;
        }

        function buildSparklineCoordinates(series) {
            if (!Array.isArray(series) || series.length < 2) {
                return [];
            }

            const minValue = Math.min(...series);
            const maxValue = Math.max(...series);
            const valueRange = Math.max(maxValue - minValue, 0.0000001);

            return series.map((value, index) => {
                const x = SPARKLINE_PADDING + (index / (series.length - 1)) * (SPARKLINE_WIDTH - SPARKLINE_PADDING * 2);
                const y =
                    SPARKLINE_HEIGHT -
                    SPARKLINE_PADDING -
                    ((value - minValue) / valueRange) * (SPARKLINE_HEIGHT - SPARKLINE_PADDING * 2);

                return { x, y };
            });
        }

        function coordinatesToLinePath(coordinates) {
            return coordinates
                .map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x.toFixed(2)} ${point.y.toFixed(2)}`)
                .join(' ');
        }

        function coordinatesToAreaPath(coordinates) {
            if (coordinates.length < 2) {
                return '';
            }

            const first = coordinates[0];
            const last = coordinates[coordinates.length - 1];
            const baseline = SPARKLINE_HEIGHT - SPARKLINE_PADDING;
            const line = coordinatesToLinePath(coordinates);

            return `${line} L${last.x.toFixed(2)} ${baseline.toFixed(2)} L${first.x.toFixed(2)} ${baseline.toFixed(2)} Z`;
        }

        function renderSparkline(row, series, isPositive) {
            const sparkline = row.querySelector('[data-role="sparkline"]');
            const areaPath = row.querySelector('[data-role="sparkline-area"]');
            const linePath = row.querySelector('[data-role="sparkline-line"]');

            if (!sparkline || !areaPath || !linePath) {
                return;
            }

            if (!Array.isArray(series) || series.length < 2) {
                areaPath.setAttribute('d', '');
                linePath.setAttribute('d', '');
                return;
            }

            const coordinates = buildSparklineCoordinates(series);
            if (coordinates.length < 2) {
                areaPath.setAttribute('d', '');
                linePath.setAttribute('d', '');
                return;
            }

            linePath.setAttribute('d', coordinatesToLinePath(coordinates));
            areaPath.setAttribute('d', coordinatesToAreaPath(coordinates));
            sparkline.classList.toggle('rate-sparkline-up', Boolean(isPositive));
            sparkline.classList.toggle('rate-sparkline-down', !isPositive);
            linePath.setAttribute('stroke', isPositive ? '#34d399' : '#fb7185');
            linePath.setAttribute('stroke-width', '2.2');
            linePath.setAttribute('fill', 'none');
            linePath.setAttribute('stroke-linecap', 'round');
            linePath.setAttribute('stroke-linejoin', 'round');
            areaPath.setAttribute('fill', isPositive ? 'rgba(52, 211, 153, 0.2)' : 'rgba(251, 113, 133, 0.2)');
        }

        function updateRow(rowPayload, ghsPerUsdt) {
            const symbol = rowPayload?.symbol;
            if (!symbol || !rowBySymbol.has(symbol)) {
                return;
            }

            const tracked = rowBySymbol.get(symbol);
            if (!tracked) {
                return;
            }

            const row = tracked.row;
            const priceEl = row.querySelector('[data-role="price"]');
            const localPriceEl = row.querySelector('[data-role="local-price"]');
            const changeEl = row.querySelector('[data-role="change"]');

            const latestPrice = Number.parseFloat(rowPayload?.price ?? 'NaN');
            const changePercent = Number.parseFloat(rowPayload?.change_percent ?? 'NaN');
            let direction = null;

            if (Number.isFinite(latestPrice) && Number.isFinite(tracked.previousPrice)) {
                direction = latestPrice > tracked.previousPrice ? 'up' : latestPrice < tracked.previousPrice ? 'down' : null;
            }

            if (Number.isFinite(latestPrice)) {
                tracked.previousPrice = latestPrice;
                row.setAttribute('data-price', String(latestPrice));
                if (!Array.isArray(tracked.history)) {
                    tracked.history = [];
                }
                tracked.history.push(latestPrice);
                if (tracked.history.length > SPARKLINE_POINT_LIMIT) {
                    tracked.history = tracked.history.slice(-SPARKLINE_POINT_LIMIT);
                }
            }

            if (priceEl) {
                priceEl.textContent = formatPrice(latestPrice);
                pulsePrice(priceEl, direction);
            }

            if (localPriceEl) {
                const localPrice = Number.isFinite(latestPrice) && Number.isFinite(ghsPerUsdt) ? latestPrice * ghsPerUsdt : Number.NaN;
                localPriceEl.textContent = formatLocal(localPrice);
                pulsePrice(localPriceEl, direction);
            }

            if (changeEl) {
                changeEl.textContent = formatChange(changePercent);
                changeEl.classList.remove('text-emerald-300', 'text-rose-300', 'text-slate-400');
                if (!Number.isFinite(changePercent)) {
                    changeEl.classList.add('text-slate-400');
                } else if (changePercent >= 0) {
                    changeEl.classList.add('text-emerald-300');
                } else {
                    changeEl.classList.add('text-rose-300');
                }
            }

            renderSparkline(row, tracked.history, Number.isFinite(changePercent) ? changePercent >= 0 : direction !== 'down');
        }

        async function refreshMarketRates() {
            const response = await fetch(marketRatesApiEndpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch market rates');
            }

            const payload = await response.json();
            if (!Array.isArray(payload?.rows)) {
                return;
            }

            const ghsPerUsdt = resolveGhsRate(payload);
            payload.rows.forEach((rowPayload) => updateRow(rowPayload, ghsPerUsdt));

            if (lastUpdatedEl) {
                const stamp = payload?.updated_at ? new Date(payload.updated_at) : new Date();
                lastUpdatedEl.textContent = `Updated ${stamp.toLocaleTimeString()}`;
            }
        }

        refreshMarketRates().catch(() => {
            if (lastUpdatedEl) {
                lastUpdatedEl.textContent = 'Live sync delayed';
            }
        });

        window.setInterval(() => {
            refreshMarketRates().catch(() => {
                if (lastUpdatedEl) {
                    lastUpdatedEl.textContent = 'Live sync delayed';
                }
            });
        }, 20000);
    })();
</script>
</body>
</html>
