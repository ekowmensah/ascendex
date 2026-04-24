<?php

namespace App\Services;

use App\Events\PriceTickUpdated;
use App\Models\PriceTick;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PriceFeedService
{
    public const MAX_FRESH_AGE_SECONDS = 120;

    public const SUPPORTED_SYMBOLS = [
        'BTCUSDT',
        'ETHUSDT',
    ];

    public static function supportedSymbols(): array
    {
        return self::SUPPORTED_SYMBOLS;
    }

    public function fetchAndStoreLatestTicks(): array
    {
        $response = Http::timeout(5)->retry(2, 200)->get('https://api.binance.com/api/v3/ticker/price', [
            'symbols' => json_encode(self::SUPPORTED_SYMBOLS),
        ]);

        if (! $response->ok()) {
            throw new RuntimeException('Unable to fetch Binance prices.');
        }

        $items = $response->json();

        if (! is_array($items)) {
            throw new RuntimeException('Invalid Binance response.');
        }

        $tickTime = Carbon::now();
        $ticks = [];

        foreach ($items as $item) {
            $symbol = $item['symbol'] ?? null;
            $price = $item['price'] ?? null;

            if (! $symbol || ! $price || ! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
                continue;
            }

            $tick = PriceTick::query()->create([
                'symbol' => $symbol,
                'price' => (string) $price,
                'tick_time' => $tickTime,
            ]);

            PriceTickUpdated::dispatch($tick);
            $ticks[] = $tick;
        }

        foreach (self::SUPPORTED_SYMBOLS as $symbol) {
            $this->flushSymbolCache($symbol);
        }

        return $ticks;
    }

    public function fetchAndStoreSymbolTick(string $symbol): ?PriceTick
    {
        if (! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
            return null;
        }

        try {
            $response = Http::timeout(5)->retry(2, 200)->get('https://api.binance.com/api/v3/ticker/price', [
                'symbol' => $symbol,
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $item = $response->json();

        if (! is_array($item) || ($item['symbol'] ?? null) !== $symbol || ! isset($item['price'])) {
            return null;
        }

        $tick = PriceTick::query()->create([
            'symbol' => $symbol,
            'price' => (string) $item['price'],
            'tick_time' => Carbon::now(),
        ]);

        $this->flushSymbolCache($symbol);
        PriceTickUpdated::dispatch($tick);

        return $tick;
    }

    public function latestTick(string $symbol): ?PriceTick
    {
        if (! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
            return null;
        }

        return Cache::remember("price:latest-tick:{$symbol}", 1, function () use ($symbol): ?PriceTick {
            return PriceTick::query()
                ->where('symbol', $symbol)
                ->latest('tick_time')
                ->first();
        });
    }

    public function latestPrice(string $symbol): ?string
    {
        return $this->latestTick($symbol)?->price;
    }

    public function latestFreshTick(string $symbol, bool $refreshIfStale = true): ?PriceTick
    {
        if (! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
            return null;
        }

        $tick = $this->latestTick($symbol);

        if ((! $tick || ! $this->isTickFresh($tick->tick_time)) && $refreshIfStale) {
            $this->fetchAndStoreSymbolTick($symbol);
            $tick = $this->latestTick($symbol);
        }

        if (! $tick || ! $this->isTickFresh($tick->tick_time)) {
            return null;
        }

        return $tick;
    }

    public function symbolStatus(string $symbol): array
    {
        $tick = $this->latestTick($symbol);

        return [
            'symbol' => $symbol,
            'price' => $tick ? (string) $tick->price : null,
            'tick_time' => $tick?->tick_time?->toIso8601String(),
            'age_seconds' => $tick ? $this->tickAgeInSeconds($tick->tick_time) : null,
            'age_label' => $tick ? $this->tickAgeLabel($tick->tick_time) : 'No market tick recorded yet',
            'is_fresh' => $tick ? $this->isTickFresh($tick->tick_time) : false,
            'max_age_seconds' => self::MAX_FRESH_AGE_SECONDS,
        ];
    }

    public function tickAgeInSeconds(\DateTimeInterface|string|null $tickTime): ?int
    {
        $normalized = $this->normalizeTickTime($tickTime);

        if (! $normalized) {
            return null;
        }

        return max($normalized->diffInSeconds(now()), 0);
    }

    public function isTickFresh(\DateTimeInterface|string|null $tickTime, ?int $maxAgeSeconds = null): bool
    {
        $ageSeconds = $this->tickAgeInSeconds($tickTime);

        if ($ageSeconds === null) {
            return false;
        }

        return $ageSeconds <= ($maxAgeSeconds ?? self::MAX_FRESH_AGE_SECONDS);
    }

    public function tickAgeLabel(\DateTimeInterface|string|null $tickTime): string
    {
        $ageSeconds = $this->tickAgeInSeconds($tickTime);

        if ($ageSeconds === null) {
            return 'unknown age';
        }

        return $this->humanizeAgeSeconds($ageSeconds).' ago';
    }

    public function latestPriceAtOrBefore(string $symbol, Carbon $time): ?string
    {
        if (! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
            return null;
        }

        return PriceTick::query()
            ->where('symbol', $symbol)
            ->where('tick_time', '<=', $time)
            ->latest('tick_time')
            ->value('price');
    }

    public function latestSeries(string $symbol, int $limit = 120): array
    {
        if (! in_array($symbol, self::SUPPORTED_SYMBOLS, true)) {
            return [];
        }

        return Cache::remember("price:series:{$symbol}:{$limit}", 1, function () use ($symbol, $limit): array {
            return PriceTick::query()
                ->where('symbol', $symbol)
                ->latest('tick_time')
                ->limit($limit)
                ->get(['price', 'tick_time'])
                ->reverse()
                ->values()
                ->map(fn (PriceTick $tick) => [
                    'time' => $tick->tick_time->timestamp,
                    'value' => (float) $tick->price,
                ])
                ->all();
        });
    }

    public function pruneOldTicks(int $hours): int
    {
        if ($hours < 1) {
            $hours = 1;
        }

        $cutoff = now()->subHours($hours);

        return PriceTick::query()->where('tick_time', '<', $cutoff)->delete();
    }

    private function flushSymbolCache(string $symbol): void
    {
        Cache::forget("price:latest-tick:{$symbol}");
        Cache::forget("price:latest:{$symbol}");
        Cache::forget("price:series:{$symbol}:120");
    }

    private function normalizeTickTime(\DateTimeInterface|string|null $tickTime): ?Carbon
    {
        if ($tickTime instanceof Carbon) {
            return $tickTime;
        }

        if ($tickTime instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($tickTime));
        }

        if (is_string($tickTime) && trim($tickTime) !== '') {
            return Carbon::parse($tickTime);
        }

        return null;
    }

    private function humanizeAgeSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        if ($seconds < 3600) {
            $minutes = intdiv($seconds, 60);
            $remainingSeconds = $seconds % 60;

            return $remainingSeconds > 0
                ? $minutes.'m '.$remainingSeconds.'s'
                : $minutes.'m';
        }

        $hours = intdiv($seconds, 3600);
        $remainingMinutes = intdiv($seconds % 3600, 60);

        return $remainingMinutes > 0
            ? $hours.'h '.$remainingMinutes.'m'
            : $hours.'h';
    }
}
