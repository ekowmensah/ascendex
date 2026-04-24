<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\Deposit;
use App\Models\PriceTick;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class TradingPlatformFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_update_conversion_rate_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.settings.conversion'), [
            'ghs_per_usdt' => '12.5',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Conversion rate updated.');

        $this->assertSame('12.50000000', AdminSetting::getValue('ghs_per_usdt'));
    }

    public function test_admin_can_update_deposit_and_withdrawal_fee_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.settings.fees'), [
            'deposit_fee_type' => 'percentage',
            'deposit_fee_value' => '2.5',
            'withdrawal_fee_type' => 'flat',
            'withdrawal_fee_value' => '3',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Fee settings updated.');

        $this->assertSame('percentage', AdminSetting::getValue('deposit_fee_type'));
        $this->assertSame('2.50000000', AdminSetting::getValue('deposit_fee_value'));
        $this->assertSame('flat', AdminSetting::getValue('withdrawal_fee_type'));
        $this->assertSame('3.00000000', AdminSetting::getValue('withdrawal_fee_value'));
    }

    public function test_withdrawal_request_holds_balance_and_creates_pending_withdrawal(): void
    {
        $this->setConversionRate('10.00000000');

        $user = User::factory()->create(['role' => 'user']);
        $this->createWallet($user, '100.00000000');

        $response = $this->actingAs($user)->post(route('withdrawal.store'), [
            'submission_token' => 'withdrawal-request-token',
            'wallet_currency' => 'USDT',
            'amount' => '300',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'account_name' => 'Trader User',
            'account_number' => '0241234567',
            'account_phone' => '+233241234567',
            'note' => 'Test payout',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Withdrawal request submitted.');

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'currency' => 'USDT',
            'amount' => '30.00000000',
            'local_amount' => '300.00',
            'conversion_rate' => '10.00000000',
            'mobile_provider' => 'mtn',
            'account_name' => 'Trader User',
            'account_number' => '0241234567',
            'account_phone' => '+233241234567',
            'status' => 'PENDING',
        ]);

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('70.00000000', $wallet->balance);
        $this->assertSame('30.00000000', $wallet->locked_balance);

        $withdrawalId = (int) $user->withdrawals()->latest('id')->value('id');
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdrawal_hold',
            'reference' => 'withdrawal-'.$withdrawalId,
            'amount' => '-30.00000000',
        ]);
    }

    public function test_admin_withdrawal_approval_consumes_hold_and_is_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $wallet = $this->createWallet($user, '70.00000000', '30.00000000');

        $withdrawal = $user->withdrawals()->create([
            'currency' => 'USDT',
            'amount' => '30.00000000',
            'local_amount' => '300.00',
            'conversion_rate' => '10.00000000',
            'destination' => 'ADDR',
            'status' => 'PENDING',
            'note' => null,
        ]);

        Transaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal_hold',
            'amount' => '-30.00000000',
            'balance_before' => '100.00000000',
            'balance_after' => '70.00000000',
            'reference' => 'withdrawal-'.$withdrawal->id,
            'meta' => ['withdrawal_id' => $withdrawal->id, 'currency' => 'USDT'],
        ]);

        $first = $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal));
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Withdrawal approved.');

        $wallet->refresh();
        $this->assertSame('70.00000000', $wallet->balance);
        $this->assertSame('0.00000000', $wallet->locked_balance);

        $withdrawal->refresh();
        $this->assertSame('APPROVED', $withdrawal->status);
        $this->assertSame($admin->id, $withdrawal->approved_by);
        $this->assertNotNull($withdrawal->approved_at);

        $this->assertSame(1, Transaction::query()
            ->where('type', 'withdrawal_settle')
            ->where('reference', 'withdrawal-'.$withdrawal->id)
            ->count());

        $second = $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal));
        $second->assertRedirect();
        $second->assertSessionHasErrors(['withdrawal']);
    }

    public function test_withdrawal_request_uses_fee_snapshot_and_holds_full_requested_amount(): void
    {
        $this->setConversionRate('10.00000000');
        AdminSetting::query()->create(['key' => 'withdrawal_fee_type', 'value' => 'flat']);
        AdminSetting::query()->create(['key' => 'withdrawal_fee_value', 'value' => '5.00000000']);

        $user = User::factory()->create(['role' => 'user']);
        $this->createWallet($user, '100.00000000');

        $response = $this->actingAs($user)->post(route('withdrawal.store'), [
            'submission_token' => 'withdrawal-fee-token',
            'wallet_currency' => 'USDT',
            'amount' => '300',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'account_name' => 'Trader User',
            'account_number' => '0241234567',
            'account_phone' => '+233241234567',
            'note' => 'Fee snapshot test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Withdrawal request submitted.');

        $withdrawal = Withdrawal::query()->latest('id')->firstOrFail();
        $this->assertSame('USDT', $withdrawal->currency);
        $this->assertSame('30.00000000', $withdrawal->amount);
        $this->assertSame('300.00', $withdrawal->local_amount);
        $this->assertSame('10.00000000', $withdrawal->conversion_rate);
        $this->assertSame('mtn', $withdrawal->mobile_provider);
        $this->assertSame('Trader User', $withdrawal->account_name);
        $this->assertSame('0241234567', $withdrawal->account_number);
        $this->assertSame('+233241234567', $withdrawal->account_phone);
        $this->assertSame('flat', $withdrawal->fee_type);
        $this->assertSame('5.00000000', $withdrawal->fee_value);
        $this->assertSame('0.50000000', $withdrawal->fee_amount);
        $this->assertSame('29.50000000', $withdrawal->net_amount);

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('70.00000000', $wallet->balance);
        $this->assertSame('30.00000000', $wallet->locked_balance);
    }

    public function test_admin_deposit_approval_credits_once_even_when_called_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->createWallet($user, '50.00000000');

        $deposit = Deposit::query()->create([
            'user_id' => $user->id,
            'currency' => 'USDT',
            'amount' => '25.00000000',
            'local_amount' => '250.00',
            'conversion_rate' => '10.00000000',
            'payment_method' => 'mobile_money',
            'proof_path' => 'deposit-proofs/test.pdf',
            'status' => 'PENDING',
            'note' => null,
        ]);

        $first = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Deposit approved.');

        $second = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $second->assertRedirect();
        $second->assertSessionHasErrors(['deposit']);

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('75.00000000', $wallet->balance);
        $this->assertSame(1, Transaction::query()
            ->where('type', 'deposit')
            ->where('reference', 'deposit-'.$deposit->id)
            ->count());
    }

    public function test_deposit_submission_and_approval_use_saved_fee_snapshot(): void
    {
        Storage::fake('local');

        $this->setConversionRate('10.00000000');
        AdminSetting::query()->create(['key' => 'deposit_fee_type', 'value' => 'percentage']);
        AdminSetting::query()->create(['key' => 'deposit_fee_value', 'value' => '10.00000000']);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->createWallet($user, '0.00000000');

        $submission = $this->actingAs($user)->post(route('deposit.store'), [
            'submission_token' => 'fee-snapshot-token',
            'wallet_currency' => 'USDT',
            'amount' => '500',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'Trader User',
            'sender_phone' => '+233501234567',
            'transaction_reference' => 'MOMO-10001',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'note' => 'Deposit fee snapshot',
        ]);

        $submission->assertRedirect();
        $submission->assertSessionHas('status', 'Deposit request submitted.');

        $deposit = Deposit::query()->latest('id')->firstOrFail();
        $this->assertSame('USDT', $deposit->currency);
        $this->assertSame('500.00', $deposit->local_amount);
        $this->assertSame('10.00000000', $deposit->conversion_rate);
        $this->assertSame('50.00000000', $deposit->amount);
        $this->assertSame('mtn', $deposit->mobile_provider);
        $this->assertSame('Trader User', $deposit->sender_name);
        $this->assertSame('+233501234567', $deposit->sender_phone);
        $this->assertSame('MOMO-10001', $deposit->transaction_reference);
        $this->assertSame('percentage', $deposit->fee_type);
        $this->assertSame('10.00000000', $deposit->fee_value);
        $this->assertSame('5.00000000', $deposit->fee_amount);
        $this->assertSame('45.00000000', $deposit->net_amount);

        AdminSetting::query()->updateOrCreate(['key' => 'deposit_fee_type'], ['value' => 'flat']);
        AdminSetting::query()->updateOrCreate(['key' => 'deposit_fee_value'], ['value' => '1.00000000']);

        $approval = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $approval->assertRedirect();
        $approval->assertSessionHas('status', 'Deposit approved.');

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('45.00000000', $wallet->balance);
        $this->assertSame(1, Transaction::query()
            ->where('type', 'deposit')
            ->where('reference', 'deposit-'.$deposit->id)
            ->where('amount', '45.00000000')
            ->count());
    }

    public function test_btc_deposit_request_and_approval_credit_the_btc_wallet(): void
    {
        Storage::fake('local');

        $this->setConversionRate('10.00000000');
        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '100.00000000',
            'tick_time' => now(),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->createWallet($user, '0.00000000', '0.00000000', 'BTC');

        $submission = $this->actingAs($user)->post(route('deposit.store'), [
            'submission_token' => 'btc-deposit-token',
            'wallet_currency' => 'BTC',
            'amount' => '1000',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'Trader User',
            'sender_phone' => '+233501234567',
            'transaction_reference' => 'BTC-MOMO-10001',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'note' => 'BTC wallet top-up',
        ]);

        $submission->assertRedirect();

        $deposit = Deposit::query()->latest('id')->firstOrFail();
        $this->assertSame('BTC', $deposit->currency);
        $this->assertSame('1000.00', $deposit->local_amount);
        $this->assertSame('1000.00000000', $deposit->conversion_rate);
        $this->assertSame('1.00000000', $deposit->amount);
        $this->assertSame('1.00000000', $deposit->net_amount);

        $approval = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $approval->assertRedirect();
        $approval->assertSessionHas('status', 'Deposit approved.');

        $btcWallet = $user->wallets()->where('currency', 'BTC')->firstOrFail();
        $this->assertSame('1.00000000', $btcWallet->balance);
    }

    public function test_admin_deposit_rejection_marks_request_processed_without_crediting_wallet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->createWallet($user, '50.00000000');

        $deposit = Deposit::query()->create([
            'user_id' => $user->id,
            'currency' => 'USDT',
            'amount' => '25.00000000',
            'local_amount' => '250.00',
            'conversion_rate' => '10.00000000',
            'payment_method' => 'mobile_money',
            'proof_path' => 'deposit-proofs/test.pdf',
            'status' => 'PENDING',
            'note' => null,
        ]);

        $first = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit));
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Deposit rejected.');

        $deposit->refresh();
        $this->assertSame('REJECTED', $deposit->status);
        $this->assertSame($admin->id, $deposit->approved_by);
        $this->assertNotNull($deposit->approved_at);

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('50.00000000', $wallet->balance);
        $this->assertSame(0, Transaction::query()
            ->where('reference', 'deposit-'.$deposit->id)
            ->count());
    }

    public function test_price_api_rejects_unsupported_symbol(): void
    {
        $this->getJson('/api/prices/latest?symbol=NOTREAL')
            ->assertStatus(422);
    }

    public function test_withdrawal_request_rejects_when_fee_consumes_full_amount(): void
    {
        $this->setConversionRate('10.00000000');
        AdminSetting::query()->create(['key' => 'withdrawal_fee_type', 'value' => 'flat']);
        AdminSetting::query()->create(['key' => 'withdrawal_fee_value', 'value' => '300.00000000']);

        $user = User::factory()->create(['role' => 'user']);
        $this->createWallet($user, '100.00000000');

        $response = $this->actingAs($user)->post(route('withdrawal.store'), [
            'submission_token' => 'withdrawal-invalid-fee-token',
            'wallet_currency' => 'USDT',
            'amount' => '300',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'account_name' => 'Trader User',
            'account_number' => '0241234567',
            'account_phone' => '+233241234567',
            'note' => 'Invalid fee test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount']);
        $this->assertSame(0, Withdrawal::query()->count());
    }

    public function test_deposit_submission_token_prevents_duplicate_records(): void
    {
        Storage::fake('local');
        $this->setConversionRate('10.00000000');

        $user = User::factory()->create(['role' => 'user']);

        $payload = [
            'submission_token' => 'duplicate-token-123',
            'wallet_currency' => 'USDT',
            'amount' => '500',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'Trader User',
            'sender_phone' => '+233501234567',
            'transaction_reference' => 'MOMO-847392',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'note' => 'Manual top-up',
        ];

        $first = $this->actingAs($user)->post(route('deposit.store'), $payload);
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Deposit request submitted.');

        $payload['proof'] = UploadedFile::fake()->image('proof-2.jpg');
        $second = $this->actingAs($user)->post(route('deposit.store'), $payload);
        $second->assertRedirect();
        $second->assertSessionHasErrors(['deposit']);

        $this->assertSame(1, Deposit::query()->where('user_id', $user->id)->count());
    }

    public function test_withdrawal_submission_token_prevents_duplicate_records_and_duplicate_holds(): void
    {
        $this->setConversionRate('10.00000000');

        $user = User::factory()->create(['role' => 'user']);
        $this->createWallet($user, '100.00000000');

        $payload = [
            'submission_token' => 'duplicate-withdrawal-token',
            'wallet_currency' => 'USDT',
            'amount' => '300',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'account_name' => 'Trader User',
            'account_number' => '0241234567',
            'account_phone' => '+233241234567',
            'note' => 'Duplicate submission test',
        ];

        $first = $this->actingAs($user)->post(route('withdrawal.store'), $payload);
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Withdrawal request submitted.');

        $second = $this->actingAs($user)->post(route('withdrawal.store'), $payload);
        $second->assertRedirect();
        $second->assertSessionHasErrors(['withdrawal']);

        $wallet = $user->wallet()->firstOrFail();
        $this->assertSame('70.00000000', $wallet->balance);
        $this->assertSame('30.00000000', $wallet->locked_balance);
        $this->assertSame(1, Withdrawal::query()->where('user_id', $user->id)->count());
    }

    public function test_deposit_transaction_reference_is_blocked_across_users(): void
    {
        Storage::fake('local');
        $this->setConversionRate('10.00000000');

        $firstUser = User::factory()->create(['role' => 'user']);
        $secondUser = User::factory()->create(['role' => 'user']);

        $first = $this->actingAs($firstUser)->post(route('deposit.store'), [
            'submission_token' => 'first-ref-token',
            'wallet_currency' => 'USDT',
            'amount' => '500',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'First User',
            'sender_phone' => '+233501111111',
            'transaction_reference' => 'GLOBAL-MOMO-REF-1',
            'proof' => UploadedFile::fake()->image('proof-1.jpg'),
            'note' => 'First deposit',
        ]);

        $first->assertRedirect();
        $first->assertSessionHas('status', 'Deposit request submitted.');

        $second = $this->actingAs($secondUser)->post(route('deposit.store'), [
            'submission_token' => 'second-ref-token',
            'wallet_currency' => 'USDT',
            'amount' => '300',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'Second User',
            'sender_phone' => '+233502222222',
            'transaction_reference' => 'GLOBAL-MOMO-REF-1',
            'proof' => UploadedFile::fake()->image('proof-2.jpg'),
            'note' => 'Replay attempt',
        ]);

        $second->assertRedirect();
        $second->assertSessionHasErrors(['deposit']);
        $this->assertSame(1, Deposit::query()->count());
    }

    public function test_btc_deposit_requires_a_fresh_market_tick(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://api.binance.com/*' => Http::response([], 500),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00'));
        $this->setConversionRate('10.00000000');

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '100.00000000',
            'tick_time' => now()->subMinutes(10),
        ]);

        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('deposit.store'), [
            'submission_token' => 'stale-btc-deposit-token',
            'wallet_currency' => 'BTC',
            'amount' => '1000',
            'payment_method' => 'mobile_money',
            'mobile_provider' => 'mtn',
            'sender_name' => 'Trader User',
            'sender_phone' => '+233501234567',
            'transaction_reference' => 'STALE-BTC-10001',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'note' => 'Stale BTC rate test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['amount']);
        $this->assertSame(0, Deposit::query()->count());
    }

    public function test_trade_submission_token_prevents_duplicate_debits(): void
    {
        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '100.00000000',
            'tick_time' => now(),
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $this->createWallet($user, '1.00000000', '0.00000000', 'BTC');

        $payload = [
            'submission_token' => 'duplicate-trade-token',
            'symbol' => 'BTCUSDT',
            'wallet_currency' => 'BTC',
            'amount' => '0.10000000',
            'duration' => 1,
            'direction' => 'UP',
        ];

        $first = $this->actingAs($user)->post(route('trade.store'), $payload);
        $first->assertRedirect();
        $first->assertSessionHas('status', 'Trade placed successfully.');

        $second = $this->actingAs($user)->post(route('trade.store'), $payload);
        $second->assertRedirect();
        $second->assertSessionHasErrors(['trade']);

        $wallet = $user->wallets()->where('currency', 'BTC')->firstOrFail();
        $this->assertSame('0.90000000', $wallet->balance);
        $this->assertSame(1, $user->trades()->count());
    }

    public function test_recent_trades_endpoint_settles_overdue_trades_for_the_authenticated_user(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00'));

        $user = User::factory()->create(['role' => 'user']);
        $wallet = $this->createWallet($user, '0.90000000', '0.00000000', 'BTC');

        PriceTick::query()->create([
            'symbol' => 'BTCUSDT',
            'price' => '110.00000000',
            'tick_time' => now()->subSeconds(30),
        ]);

        $trade = Trade::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'UP',
            'amount' => '0.10000000',
            'entry_price' => '100.00000000',
            'payout_rate' => '1.8000',
            'status' => 'PENDING',
            'expiry_time' => now()->subSeconds(10),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('trade.recent'));

        $response->assertOk()
            ->assertJsonPath('trades.0.id', $trade->id)
            ->assertJsonPath('trades.0.status', 'WIN');

        $this->assertSame('WIN', $trade->fresh()->status);
        $this->assertSame('1.08000000', $wallet->fresh()->balance);
    }

    public function test_legacy_ascendex_urls_redirect_to_the_root_mounted_app(): void
    {
        $this->get('/ascendex/login')
            ->assertRedirect('/login');

        $this->get('/ascendex/trade/recent?demo=1')
            ->assertStatus(307)
            ->assertRedirect('/trade/recent?demo=1');
    }

    public function test_stale_app_asset_requests_redirect_to_the_latest_vite_entry(): void
    {
        $this->get('/build/assets/app-L4qWrOdr.css')
            ->assertRedirect(Vite::asset('resources/css/app.css'));
    }

    private function createWallet(
        User $user,
        string $balance,
        string $lockedBalance = '0.00000000',
        string $currency = 'USDT'
    ): Wallet {
        return Wallet::query()->create([
            'user_id' => $user->id,
            'currency' => $currency,
            'balance' => $balance,
            'bonus' => '0.00000000',
            'locked_balance' => $lockedBalance,
        ]);
    }

    private function setConversionRate(string $rate): void
    {
        AdminSetting::query()->updateOrCreate(['key' => 'ghs_per_usdt'], ['value' => $rate]);
    }
}
