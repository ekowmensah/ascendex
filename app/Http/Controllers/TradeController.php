<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceTradeRequest;
use App\Models\Trade;
use App\Services\CurrencyConversionService;
use App\Services\PriceFeedService;
use App\Services\TradeService;
use App\Services\WalletService;
use App\Support\WalletCurrency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TradeController extends Controller
{
    public function index(
        Request $request,
        PriceFeedService $priceFeedService,
        WalletService $walletService,
        CurrencyConversionService $currencyConversionService,
        TradeService $tradeService,
    )
    {
        $user = $request->user();
        $tradeService->settleExpiredTrades(limit: 50, userId: $user->id);
        $wallets = $walletService->ensureSupportedWallets($user)->keyBy('currency');
        $recentTrades = $this->recentTradesForUser($user->id);

        return view('trade.index', [
            'wallets' => $wallets,
            'walletsPayload' => $wallets->mapWithKeys(fn ($wallet) => [
                $wallet->currency => [
                    'currency' => $wallet->currency,
                    'balance' => (float) $wallet->balance,
                    'locked_balance' => (float) $wallet->locked_balance,
                    'label' => WalletCurrency::label($wallet->currency),
                ],
            ]),
            'walletCurrencyOptions' => $currencyConversionService->getWalletCurrencyOptions(),
            'selectedTradeCurrency' => (string) old('wallet_currency', WalletCurrency::DEFAULT),
            'recentTrades' => $recentTrades,
            'recentTradesPayload' => $recentTrades->map(fn (Trade $trade) => $this->serializeTrade($trade))->values(),
            'btcSeries' => $priceFeedService->latestSeries('BTCUSDT', 3600),
            'ethSeries' => $priceFeedService->latestSeries('ETHUSDT', 3600),
            'btcPrice' => $priceFeedService->latestPrice('BTCUSDT'),
            'ethPrice' => $priceFeedService->latestPrice('ETHUSDT'),
            'symbolStatusMap' => collect(PriceFeedService::supportedSymbols())->mapWithKeys(
                fn (string $symbol) => [$symbol => $priceFeedService->symbolStatus($symbol)]
            )->all(),
            'submissionToken' => Str::uuid()->toString(),
        ]);
    }

    public function store(PlaceTradeRequest $request, TradeService $tradeService): RedirectResponse
    {
        $validated = $request->validated();
        $token = $validated['submission_token'];
        $idempotencyKey = sprintf('trade:submit:%d:%s', $request->user()->id, $token);

        if (! Cache::add($idempotencyKey, true, now()->addMinutes(10))) {
            return back()->withErrors(['trade' => 'Duplicate trade submission detected. Please wait before retrying.']);
        }

        try {
            $tradeService->placeTrade($request->user(), $validated);
        } catch (\RuntimeException $exception) {
            Cache::forget($idempotencyKey);

            return back()->withErrors(['trade' => $exception->getMessage()]);
        }

        return back()->with('status', 'Trade placed successfully.');
    }

    public function recent(Request $request, TradeService $tradeService): JsonResponse
    {
        try {
            $tradeService->settleExpiredTrades(limit: 50, userId: $request->user()->id);

            $trades = $this->recentTradesForUser($request->user()->id)
                ->map(fn (Trade $trade) => $this->serializeTrade($trade))
                ->values();

            return response()->json(['trades' => $trades]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'trades' => [],
                'stale' => true,
            ], 200);
        }
    }

    private function recentTradesForUser(int $userId): Collection
    {
        return Trade::query()
            ->with('wallet')
            ->where('user_id', $userId)
            ->latest()
            ->limit(20)
            ->get();
    }

    private function serializeTrade(Trade $trade): array
    {
        return [
            'id' => $trade->id,
            'symbol' => $trade->symbol,
            'direction' => $trade->direction,
            'wallet_currency' => optional($trade->wallet)->currency ?? WalletCurrency::DEFAULT,
            'amount' => number_format((float) $trade->amount, 8),
            'amount_value' => (float) $trade->amount,
            'entry_price' => number_format((float) $trade->entry_price, 2),
            'entry_price_value' => (float) $trade->entry_price,
            'close_price' => $trade->close_price ? number_format((float) $trade->close_price, 2) : '-',
            'close_price_value' => $trade->close_price ? (float) $trade->close_price : null,
            'payout_amount' => number_format((float) $trade->payout_amount, 8),
            'payout_amount_value' => (float) $trade->payout_amount,
            'status' => $trade->status,
            'created_ts' => optional($trade->created_at)->timestamp,
            'expiry_ts' => optional($trade->expiry_time)->timestamp,
            'settled_ts' => optional($trade->settled_at)->timestamp,
            'expiry_time' => optional($trade->expiry_time)?->toIso8601String(),
        ];
    }
}
