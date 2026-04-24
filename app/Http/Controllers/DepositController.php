<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Models\Deposit;
use App\Services\CurrencyConversionService;
use App\Services\SystemFeeService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function index(
        Request $request,
        SystemFeeService $feeService,
        CurrencyConversionService $currencyConversionService,
        WalletService $walletService
    )
    {
        return view('deposit.index', [
            'deposits' => Deposit::query()->where('user_id', $request->user()->id)->latest()->paginate(20),
            'depositFeeConfig' => $feeService->getDepositFeeConfig(),
            'conversionConfig' => $currencyConversionService->getConfig((string) old('wallet_currency', 'USDT')),
            'wallets' => $walletService->ensureSupportedWallets($request->user())->keyBy('currency'),
            'submissionToken' => Str::uuid()->toString(),
        ]);
    }

    public function store(
        DepositRequest $request,
        SystemFeeService $feeService,
        CurrencyConversionService $currencyConversionService
    ): RedirectResponse
    {
        $validated = $request->validated();
        $token = $validated['submission_token'];
        $idempotencyKey = sprintf('deposit:submit:%d:%s', $request->user()->id, $token);

        if (! Cache::add($idempotencyKey, true, now()->addMinutes(10))) {
            return back()
                ->withErrors(['deposit' => 'Duplicate submission detected. Please wait before retrying.'])
                ->withInput();
        }

        $txRef = trim((string) $validated['transaction_reference']);
        $duplicateTxRefExists = Deposit::query()
            ->where('transaction_reference', $txRef)
            ->whereIn('status', ['PENDING', 'APPROVED'])
            ->exists();

        if ($duplicateTxRefExists) {
            Cache::forget($idempotencyKey);

            return back()
                ->withErrors(['deposit' => 'This transaction reference has already been submitted and is awaiting or has completed review.'])
                ->withInput();
        }

        try {
            $conversionSnapshot = $currencyConversionService->calculateSnapshotFromLocalAmount(
                $validated['amount'],
                $validated['wallet_currency']
            );
            $feeSnapshot = $feeService->calculateDepositSnapshot(
                $validated['amount'],
                $conversionSnapshot['conversion_rate']
            );
        } catch (\RuntimeException $exception) {
            Cache::forget($idempotencyKey);

            return back()
                ->withErrors(['amount' => $exception->getMessage()])
                ->withInput();
        }

        $path = $request->file('proof')->store('deposit-proofs', 'local');

        $paymentMethod = sprintf('mobile_money:%s', $validated['mobile_provider']);

        Deposit::query()->create([
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
            'payment_method' => $paymentMethod,
            'mobile_provider' => $validated['mobile_provider'],
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $validated['sender_phone'],
            'transaction_reference' => $txRef,
            'proof_path' => $path,
            'status' => 'PENDING',
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('status', 'Deposit request submitted.');
    }
}
