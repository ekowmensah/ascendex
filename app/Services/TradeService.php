<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Trade;
use App\Models\User;
use App\Support\WalletCurrency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TradeService
{
    public function __construct(
        private readonly PriceFeedService $priceFeedService,
        private readonly WalletService $walletService,
    ) {
    }

    public function placeTrade(User $user, array $payload): Trade
    {
        $symbol = $payload['symbol'];
        $walletCurrency = WalletCurrency::normalize((string) ($payload['wallet_currency'] ?? WalletCurrency::DEFAULT));
        $amount = $this->normalizeDecimal($payload['amount']);
        $duration = (int) $payload['duration'];
        $direction = $payload['direction'];
        $entryTick = $this->priceFeedService->latestFreshTick($symbol);

        if (! $entryTick || ! $entryTick->price || $this->decimalCompare((string) $entryTick->price, '0') <= 0) {
            $status = $this->priceFeedService->symbolStatus($symbol);
            $message = $status['age_seconds'] === null
                ? 'Live price unavailable. Ensure price feed is running, then try again.'
                : 'Live price unavailable or stale. Last market tick: '.$status['age_label'].'.';

            throw new \RuntimeException($message);
        }

        $entryPrice = (string) $entryTick->price;

        $payoutRate = $this->normalizeDecimal(AdminSetting::getValue('payout_rate', '1.80000000'));

        return DB::transaction(function () use ($user, $symbol, $walletCurrency, $amount, $duration, $direction, $entryPrice, $payoutRate): Trade {
            $wallet = $this->walletService->debit($user, $amount, 'trade', null, [
                'symbol' => $symbol,
                'direction' => $direction,
                'duration' => $duration,
                'wallet_currency' => $walletCurrency,
            ], $walletCurrency);

            return Trade::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'symbol' => $symbol,
                'direction' => $direction,
                'amount' => $amount,
                'entry_price' => $this->normalizeDecimal($entryPrice),
                'payout_rate' => $payoutRate,
                'status' => 'PENDING',
                'expiry_time' => Carbon::now()->addMinutes($duration),
            ]);
        });
    }

    public function settleExpiredTrades(int $limit = 200, ?int $userId = null): int
    {
        $tradeIdsQuery = Trade::query()
            ->where('status', 'PENDING')
            ->where('expiry_time', '<=', now())
            ->orderBy('expiry_time');

        if ($userId !== null) {
            $tradeIdsQuery->where('user_id', $userId);
        }

        $tradeIds = $tradeIdsQuery
            ->limit($limit)
            ->pluck('id');

        $settled = 0;

        foreach ($tradeIds as $tradeId) {
            $didSettle = DB::transaction(function () use ($tradeId): bool {
                $trade = Trade::query()
                    ->with('user')
                    ->whereKey($tradeId)
                    ->lockForUpdate()
                    ->first();

                if (! $trade || $trade->status !== 'PENDING') {
                    return false;
                }

                $closePrice = $this->priceFeedService->latestPriceAtOrBefore($trade->symbol, $trade->expiry_time)
                    ?? $this->priceFeedService->latestPrice($trade->symbol);

                if (! $closePrice) {
                    return false;
                }

                $entryPrice = $this->normalizeDecimal($trade->entry_price);
                $closePrice = $this->normalizeDecimal($closePrice);

                $isWin = ($trade->direction === 'UP' && $this->decimalCompare($closePrice, $entryPrice) > 0)
                    || ($trade->direction === 'DOWN' && $this->decimalCompare($closePrice, $entryPrice) < 0);

                $trade->close_price = $closePrice;
                $trade->status = $isWin ? 'WIN' : 'LOSE';
                $trade->settled_at = now();
                $trade->payout_amount = $isWin
                    ? $this->decimalMul($this->normalizeDecimal($trade->amount), $this->normalizeDecimal($trade->payout_rate))
                    : '0.00000000';
                $trade->save();

                if ($isWin) {
                    $this->walletService->credit(
                        $trade->user,
                        $trade->payout_amount,
                        'profit',
                        'trade-'.$trade->id,
                        ['trade_id' => $trade->id],
                        (string) optional($trade->wallet)->currency
                    );
                }

                return true;
            });

            if ($didSettle) {
                $settled++;
            }
        }

        return $settled;
    }

    private function normalizeDecimal(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00000000';
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        if (! is_numeric($value)) {
            throw new \RuntimeException('Invalid numeric value.');
        }

        if (function_exists('bcadd')) {
            return bcadd($value, '0', 8);
        }

        return number_format((float) $value, 8, '.', '');
    }

    private function decimalCompare(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 8);
        }

        return ((float) $left) <=> ((float) $right);
    }

    private function decimalMul(string $left, string $right): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, 8);
        }

        return number_format((float) $left * (float) $right, 8, '.', '');
    }
}
