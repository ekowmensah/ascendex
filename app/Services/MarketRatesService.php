<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketRatesService
{
    public const SYMBOLS = [
        'BTCUSDT',
        'ETHUSDT',
        'DOGEUSDT',
        'BCHUSDT',
        'LTCUSDT',
        'TRXUSDT',
        'XRPUSDT',
        'IOTAUSDT',
        'FILUSDT',
        'SHIBUSDT',
        'FLOWUSDT',
        'JSTUSDT',
        'ADAUSDT',
        'BSVUSDT',
        'USDCUSDT',
        'USDPUSDT',
        'TUSDUSDT',
    ];

    private const COINGECKO_IDS = [
        'BTCUSDT' => 'bitcoin',
        'ETHUSDT' => 'ethereum',
        'DOGEUSDT' => 'dogecoin',
        'BCHUSDT' => 'bitcoin-cash',
        'LTCUSDT' => 'litecoin',
        'TRXUSDT' => 'tron',
        'XRPUSDT' => 'ripple',
        'IOTAUSDT' => 'iota',
        'FILUSDT' => 'filecoin',
        'SHIBUSDT' => 'shiba-inu',
        'FLOWUSDT' => 'flow',
        'JSTUSDT' => 'just',
        'ADAUSDT' => 'cardano',
        'BSVUSDT' => 'bitcoin-sv',
        'USDCUSDT' => 'usd-coin',
        'USDPUSDT' => 'pax-dollar',
        'TUSDUSDT' => 'true-usd',
    ];

    public function symbols(): array
    {
        return self::SYMBOLS;
    }

    public function snapshot(float $ghsPerUsdt): array
    {
        $tickerRows = $this->fetchTickerRows();

        return collect(self::SYMBOLS)->map(function (string $symbol) use ($tickerRows, $ghsPerUsdt): array {
            $tickerRow = $tickerRows[$symbol] ?? null;

            return $this->buildCoinRow($symbol, is_array($tickerRow) ? $tickerRow : null, $ghsPerUsdt);
        })->all();
    }

    private function fetchTickerRows(): array
    {
        return Cache::remember('market-rates:ticker-rows', 10, function (): array {
            $binanceRows = $this->fetchBinanceRows(self::SYMBOLS);

            if (count($binanceRows) === count(self::SYMBOLS)) {
                return $binanceRows;
            }

            $missingSymbols = array_values(array_diff(self::SYMBOLS, array_keys($binanceRows)));
            $fallbackRows = $this->fetchCoinGeckoRows($missingSymbols);

            return array_merge($binanceRows, $fallbackRows);
        });
    }

    private function fetchBinanceRows(array $symbols): array
    {
        try {
            $response = Http::timeout(6)->retry(2, 200)->get('https://api.binance.com/api/v3/ticker/24hr', [
                'symbols' => json_encode($symbols),
            ]);
        } catch (\Throwable) {
            return [];
        }

        $items = $response->json();

        if (! $response->ok() || ! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && isset($item['symbol']))
            ->mapWithKeys(fn (array $item): array => [$item['symbol'] => $item])
            ->all();
    }

    private function fetchCoinGeckoRows(array $symbols): array
    {
        $ids = collect($symbols)
            ->map(fn (string $symbol): ?string => self::COINGECKO_IDS[$symbol] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        try {
            $response = Http::timeout(6)->retry(1, 200)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => $ids->implode(','),
                'vs_currencies' => 'usd',
                'include_24hr_change' => 'true',
            ]);
        } catch (\Throwable) {
            return [];
        }

        $items = $response->json();

        if (! $response->ok() || ! is_array($items)) {
            return [];
        }

        $rows = [];

        foreach ($symbols as $symbol) {
            $coinId = self::COINGECKO_IDS[$symbol] ?? null;
            $coinRow = $coinId !== null ? ($items[$coinId] ?? null) : null;
            $changeValue = is_array($coinRow)
                ? ($coinRow['usd_24h_change'] ?? $coinRow['usd_24hr_change'] ?? null)
                : null;

            if (! is_array($coinRow) || ! is_numeric($coinRow['usd'] ?? null)) {
                continue;
            }

            $rows[$symbol] = [
                'symbol' => $symbol,
                'lastPrice' => (string) $coinRow['usd'],
                'priceChangePercent' => is_numeric($changeValue)
                    ? (string) $changeValue
                    : null,
            ];
        }

        return $rows;
    }

    private function buildCoinRow(string $symbol, ?array $tickerRow, float $ghsPerUsdt): array
    {
        $base = str_replace('USDT', '', $symbol);
        $price = $this->toFloat($tickerRow['lastPrice'] ?? null);
        $changePercent = $this->toFloat($tickerRow['priceChangePercent'] ?? null);

        return [
            'symbol' => $symbol,
            'base' => $base,
            'quote' => 'USDT',
            'price' => $price,
            'price_label' => $price !== null ? $this->formatPrice($price) : '--',
            'change_percent' => $changePercent,
            'change_label' => $changePercent !== null ? sprintf('%+.2f%%', $changePercent) : '--',
            'is_positive' => $changePercent !== null ? $changePercent >= 0 : false,
            'local_price' => $price !== null ? ($price * $ghsPerUsdt) : null,
            'local_price_label' => $price !== null ? number_format($price * $ghsPerUsdt, 2) : '--',
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function formatPrice(float $price): string
    {
        if ($price >= 1000) {
            return number_format($price, 2, '.', '');
        }

        if ($price >= 1) {
            return number_format($price, 4, '.', '');
        }

        if ($price >= 0.01) {
            return number_format($price, 6, '.', '');
        }

        return number_format($price, 8, '.', '');
    }
}
