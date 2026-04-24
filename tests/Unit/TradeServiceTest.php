<?php

namespace Tests\Unit;

use App\Models\AdminSetting;
use App\Models\PriceTick;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TradeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_trade_settlement_uses_expiry_price_and_credits_winner_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-16 12:00:00'));

        $user = User::factory()->create(['role' => 'user']);
        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'BTC',
            'balance' => '1.00000000',
            'bonus' => '0.00000000',
            'locked_balance' => '0.00000000',
        ]);

        AdminSetting::query()->create(['key' => 'payout_rate', 'value' => '1.80000000']);
        AdminSetting::query()->create(['key' => 'payout_percent', 'value' => '80']);

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '100.00000000',
            'tick_time' => now(),
        ]);

        /** @var TradeService $tradeService */
        $tradeService = app(TradeService::class);

        $trade = $tradeService->placeTrade($user, [
            'symbol' => 'BTCUSDT',
            'wallet_currency' => 'BTC',
            'amount' => '0.10000000',
            'duration' => 1,
            'direction' => 'UP',
        ]);

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '105.00000000',
            'tick_time' => $trade->expiry_time->copy()->subSecond(),
        ]);

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '95.00000000',
            'tick_time' => $trade->expiry_time->copy()->addSeconds(5),
        ]);

        Carbon::setTestNow($trade->expiry_time->copy()->addSeconds(10));
        $settled = $tradeService->settleExpiredTrades();

        $this->assertSame(1, $settled);

        $trade->refresh();
        $this->assertSame('WIN', $trade->status);
        $this->assertSame('105.00000000', $trade->close_price);
        $this->assertSame('0.18000000', $trade->payout_amount);

        $wallet = $user->wallets()->where('currency', 'BTC')->firstOrFail();
        $this->assertSame('1.08000000', $wallet->balance);

        $this->assertSame(1, Transaction::query()
            ->where('type', 'profit')
            ->where('reference', 'trade-'.$trade->id)
            ->count());
    }

    public function test_place_trade_rejects_stale_entry_price(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00'));
        Http::fake([
            'https://api.binance.com/*' => Http::response([], 500),
        ]);

        $user = User::factory()->create(['role' => 'user']);
        Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => 'BTC',
            'balance' => '1.00000000',
            'bonus' => '0.00000000',
            'locked_balance' => '0.00000000',
        ]);

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '100.00000000',
            'tick_time' => now()->subMinutes(10),
        ]);

        /** @var TradeService $tradeService */
        $tradeService = app(TradeService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Live price unavailable or stale');

        $tradeService->placeTrade($user, [
            'symbol' => 'BTCUSDT',
            'wallet_currency' => 'BTC',
            'amount' => '0.10000000',
            'duration' => 1,
            'direction' => 'UP',
        ]);
    }

    public function test_settle_expired_trades_can_scope_to_a_single_user(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00'));

        $firstUser = User::factory()->create(['role' => 'user']);
        $secondUser = User::factory()->create(['role' => 'user']);

        $firstWallet = Wallet::query()->create([
            'user_id' => $firstUser->id,
            'currency' => 'BTC',
            'balance' => '0.90000000',
            'bonus' => '0.00000000',
            'locked_balance' => '0.00000000',
        ]);

        $secondWallet = Wallet::query()->create([
            'user_id' => $secondUser->id,
            'currency' => 'BTC',
            'balance' => '0.90000000',
            'bonus' => '0.00000000',
            'locked_balance' => '0.00000000',
        ]);

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '110.00000000',
            'tick_time' => now()->subSeconds(30),
        ]);

        $firstTrade = $firstUser->trades()->create([
            'wallet_id' => $firstWallet->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'UP',
            'amount' => '0.10000000',
            'entry_price' => '100.00000000',
            'payout_rate' => '1.8000',
            'status' => 'PENDING',
            'expiry_time' => now()->subSeconds(10),
        ]);

        $secondTrade = $secondUser->trades()->create([
            'wallet_id' => $secondWallet->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'UP',
            'amount' => '0.10000000',
            'entry_price' => '100.00000000',
            'payout_rate' => '1.8000',
            'status' => 'PENDING',
            'expiry_time' => now()->subSeconds(10),
        ]);

        /** @var TradeService $tradeService */
        $tradeService = app(TradeService::class);
        $settled = $tradeService->settleExpiredTrades(limit: 10, userId: $firstUser->id);

        $this->assertSame(1, $settled);
        $this->assertSame('WIN', $firstTrade->fresh()->status);
        $this->assertSame('PENDING', $secondTrade->fresh()->status);
        $this->assertSame('1.08000000', $firstWallet->fresh()->balance);
        $this->assertSame('0.90000000', $secondWallet->fresh()->balance);
    }
}
