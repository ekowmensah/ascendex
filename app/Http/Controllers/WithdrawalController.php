<?php

namespace App\Http\Controllers;

use App\Http\Requests\WithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\CurrencyConversionService;
use App\Services\SystemFeeService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function index(
        Request $request,
        SystemFeeService $feeService,
        CurrencyConversionService $currencyConversionService,
        WalletService $walletService
    )
    {
        return view('withdrawal.index', [
            'withdrawals' => Withdrawal::query()->where('user_id', $request->user()->id)->latest()->paginate(20),
            'withdrawalFeeConfig' => $feeService->getWithdrawalFeeConfig(),
            'conversionConfig' => $currencyConversionService->getConfig((string) old('wallet_currency', 'USDT')),
            'wallets' => $walletService->ensureSupportedWallets($request->user())->keyBy('currency'),
            'submissionToken' => Str::uuid()->toString(),
        ]);
    }

    public function store(
        WithdrawalRequest $request,
        WalletService $walletService,
        SystemFeeService $feeService,
        CurrencyConversionService $currencyConversionService
    ): RedirectResponse
    {
        $validated = $request->validated();
        $token = $validated['submission_token'];
        $idempotencyKey = sprintf('withdrawal:submit:%d:%s', $request->user()->id, $token);

        if (! Cache::add($idempotencyKey, true, now()->addMinutes(10))) {
            return back()
                ->withErrors(['withdrawal' => 'Duplicate submission detected. Please wait before retrying.'])
                ->withInput();
        }

        try {
            $conversionSnapshot = $currencyConversionService->calculateSnapshotFromLocalAmount(
                $validated['amount'],
                $validated['wallet_currency']
            );
            $feeSnapshot = $feeService->calculateWithdrawalSnapshot(
                $validated['amount'],
                $conversionSnapshot['conversion_rate']
            );

            DB::transaction(function () use ($request, $walletService, $validated, $feeSnapshot, $conversionSnapshot): void {
                $destination = sprintf(
                    'mobile_money:%s:%s',
                    $validated['mobile_provider'],
                    $validated['account_number']
                );

                $withdrawal = Withdrawal::query()->create([
                    'user_id' => $request->user()->id,
                    'currency' => $conversionSnapshot['currency'],
                    'amount' => $conversionSnapshot['amount'],
                    'local_currency' => $conversionSnapshot['local_currency'],
                    'local_amount' => $conversionSnapshot['local_amount'],
                    'conversion_rate' => $conversionSnapshot['conversion_rate'],
                    'fee_type' => $feeSnapshot['fee_type'],
                    'fee_value' => $feeSnapshot['fee_value'],
                    'fee_amount' => $feeSnapshot['fee_amount'],
                    'net_amount' => $feeSnapshot['net_amount'],
                    'destination' => $destination,
                    'mobile_provider' => $validated['mobile_provider'],
                    'account_name' => $validated['account_name'],
                    'account_number' => $validated['account_number'],
                    'account_phone' => $validated['account_phone'],
                    'status' => 'PENDING',
                    'note' => $validated['note'] ?? null,
                ]);

                $walletService->hold(
                    $request->user(),
                    $withdrawal->amount,
                    'withdrawal-'.$withdrawal->id,
                    [
                        'withdrawal_id' => $withdrawal->id,
                        'wallet_currency' => $withdrawal->currency,
                        'local_currency' => $withdrawal->local_currency,
                        'local_amount' => $withdrawal->local_amount,
                        'conversion_rate' => $withdrawal->conversion_rate,
                        'destination' => $withdrawal->destination,
                        'gross_amount' => $withdrawal->amount,
                        'fee_type' => $withdrawal->fee_type,
                        'fee_value' => $withdrawal->fee_value,
                        'fee_amount' => $withdrawal->fee_amount,
                        'net_amount' => $withdrawal->net_amount,
                    ],
                    $withdrawal->currency
                );
            });
        } catch (\RuntimeException $exception) {
            Cache::forget($idempotencyKey);

            return back()->withErrors(['amount' => $exception->getMessage()]);
        }

        return back()->with('status', 'Withdrawal request submitted.');
    }
}
