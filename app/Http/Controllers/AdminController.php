<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminBalanceAdjustRequest;
use App\Http\Requests\AdminConversionRateRequest;
use App\Http\Requests\AdminFeeSettingsRequest;
use App\Http\Requests\AdminPayoutRequest;
use App\Models\AdminSetting;
use App\Models\Deposit;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\CurrencyConversionService;
use App\Services\SystemFeeService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index', [
            'stats' => $this->buildAdminStats(),
            'latestDeposits' => Deposit::query()->with('user')->latest()->limit(5)->get(),
            'latestWithdrawals' => Withdrawal::query()->with('user')->latest()->limit(5)->get(),
            'latestTrades' => Trade::query()->with(['user', 'wallet'])->latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request)
    {
        $roleOptions = ['admin', 'user'];
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => (string) $request->query('role', ''),
        ];

        if (! in_array($filters['role'], $roleOptions, true)) {
            $filters['role'] = '';
        }

        $query = User::query()->with('wallets');

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        if ($filters['role'] !== '') {
            $query->where('role', $filters['role']);
        }

        return view('admin.users', [
            'stats' => $this->buildAdminStats(),
            'users' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'roleOptions' => $roleOptions,
        ]);
    }

    public function deposits(Request $request)
    {
        $statusOptions = ['PENDING', 'APPROVED', 'REJECTED'];
        $providerOptions = ['mtn', 'airtel', 'tigo', 'vodafone', 'other'];
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'provider' => (string) $request->query('provider', ''),
            'from' => $this->parseDateFilter($request->query('from')),
            'to' => $this->parseDateFilter($request->query('to')),
        ];

        if (! in_array($filters['status'], $statusOptions, true)) {
            $filters['status'] = '';
        }

        if (! in_array($filters['provider'], $providerOptions, true)) {
            $filters['provider'] = '';
        }

        $query = Deposit::query()->with(['user', 'approver']);

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['provider'] !== '') {
            $query->where('mobile_provider', $filters['provider']);
        }

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                if (ctype_digit($q)) {
                    $builder->orWhere('id', (int) $q);
                }

                $builder->orWhere('payment_method', 'like', '%'.$q.'%')
                    ->orWhere('mobile_provider', 'like', '%'.$q.'%')
                    ->orWhere('sender_name', 'like', '%'.$q.'%')
                    ->orWhere('sender_phone', 'like', '%'.$q.'%')
                    ->orWhere('transaction_reference', 'like', '%'.$q.'%')
                    ->orWhere('note', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function (Builder $userBuilder) use ($q): void {
                        $userBuilder->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
            });
        }

        return view('admin.deposits', [
            'stats' => $this->buildAdminStats(),
            'deposits' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'providerOptions' => $providerOptions,
        ]);
    }

    public function withdrawals(Request $request)
    {
        $statusOptions = ['PENDING', 'APPROVED', 'REJECTED'];
        $providerOptions = ['mtn', 'airtel', 'tigo', 'vodafone', 'other'];
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'provider' => (string) $request->query('provider', ''),
            'from' => $this->parseDateFilter($request->query('from')),
            'to' => $this->parseDateFilter($request->query('to')),
        ];

        if (! in_array($filters['status'], $statusOptions, true)) {
            $filters['status'] = '';
        }

        if (! in_array($filters['provider'], $providerOptions, true)) {
            $filters['provider'] = '';
        }

        $query = Withdrawal::query()->with(['user', 'approver']);

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['provider'] !== '') {
            $query->where('mobile_provider', $filters['provider']);
        }

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                if (ctype_digit($q)) {
                    $builder->orWhere('id', (int) $q);
                }

                $builder->orWhere('destination', 'like', '%'.$q.'%')
                    ->orWhere('mobile_provider', 'like', '%'.$q.'%')
                    ->orWhere('account_name', 'like', '%'.$q.'%')
                    ->orWhere('account_number', 'like', '%'.$q.'%')
                    ->orWhere('account_phone', 'like', '%'.$q.'%')
                    ->orWhere('note', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function (Builder $userBuilder) use ($q): void {
                        $userBuilder->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
            });
        }

        return view('admin.withdrawals', [
            'stats' => $this->buildAdminStats(),
            'withdrawals' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'providerOptions' => $providerOptions,
        ]);
    }

    public function trades(Request $request)
    {
        $statusOptions = ['PENDING', 'WIN', 'LOSE'];
        $directionOptions = ['UP', 'DOWN'];
        $symbolOptions = ['BTCUSDT', 'ETHUSDT'];
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'direction' => (string) $request->query('direction', ''),
            'symbol' => (string) $request->query('symbol', ''),
            'from' => $this->parseDateFilter($request->query('from')),
            'to' => $this->parseDateFilter($request->query('to')),
        ];

        if (! in_array($filters['status'], $statusOptions, true)) {
            $filters['status'] = '';
        }

        if (! in_array($filters['direction'], $directionOptions, true)) {
            $filters['direction'] = '';
        }

        if (! in_array($filters['symbol'], $symbolOptions, true)) {
            $filters['symbol'] = '';
        }

        $query = Trade::query()->with('user');

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['direction'] !== '') {
            $query->where('direction', $filters['direction']);
        }

        if ($filters['symbol'] !== '') {
            $query->where('symbol', $filters['symbol']);
        }

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                if (ctype_digit($q)) {
                    $builder->orWhere('id', (int) $q);
                }

                $builder->orWhere('symbol', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function (Builder $userBuilder) use ($q): void {
                        $userBuilder->where('name', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
            });
        }

        return view('admin.trades', [
            'stats' => $this->buildAdminStats(),
            'trades' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'directionOptions' => $directionOptions,
            'symbolOptions' => $symbolOptions,
        ]);
    }

    public function settings(SystemFeeService $feeService, CurrencyConversionService $currencyConversionService)
    {
        return view('admin.settings', [
            'stats' => $this->buildAdminStats(),
            'payoutPercent' => (float) AdminSetting::getValue('payout_percent', 80),
            'payoutRate' => (float) AdminSetting::getValue('payout_rate', 1.8),
            'depositFeeConfig' => $feeService->getDepositFeeConfig(),
            'withdrawalFeeConfig' => $feeService->getWithdrawalFeeConfig(),
            'conversionConfig' => $currencyConversionService->getConfig(),
        ]);
    }

    public function viewDepositProof(Request $request, Deposit $deposit): StreamedResponse
    {
        if (! $deposit->proof_path || ! Storage::disk('local')->exists($deposit->proof_path)) {
            abort(404, 'Proof file not found.');
        }

        $ext = pathinfo($deposit->proof_path, PATHINFO_EXTENSION);
        $fileName = 'deposit-proof-'.$deposit->id.($ext ? '.'.$ext : '');

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($deposit->proof_path, $fileName);
        }

        return Storage::disk('local')->response($deposit->proof_path, $fileName, [
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function updatePayout(AdminPayoutRequest $request): RedirectResponse
    {
        $percent = $this->normalizeDecimal($request->input('payout_percent'), 4);
        $rate = $this->percentToRate($percent);

        AdminSetting::query()->updateOrCreate(['key' => 'payout_percent'], ['value' => $percent]);
        AdminSetting::query()->updateOrCreate(['key' => 'payout_rate'], ['value' => $rate]);

        return back()->with('status', 'Payout updated.');
    }

    public function updateFees(AdminFeeSettingsRequest $request): RedirectResponse
    {
        AdminSetting::query()->updateOrCreate(
            ['key' => 'deposit_fee_type'],
            ['value' => $request->input('deposit_fee_type')]
        );
        AdminSetting::query()->updateOrCreate(
            ['key' => 'deposit_fee_value'],
            ['value' => $this->normalizeDecimal($request->input('deposit_fee_value'))]
        );
        AdminSetting::query()->updateOrCreate(
            ['key' => 'withdrawal_fee_type'],
            ['value' => $request->input('withdrawal_fee_type')]
        );
        AdminSetting::query()->updateOrCreate(
            ['key' => 'withdrawal_fee_value'],
            ['value' => $this->normalizeDecimal($request->input('withdrawal_fee_value'))]
        );

        return back()->with('status', 'Fee settings updated.');
    }

    public function updateConversionRate(AdminConversionRateRequest $request): RedirectResponse
    {
        AdminSetting::query()->updateOrCreate(
            ['key' => 'ghs_per_usdt'],
            ['value' => $this->normalizeDecimal($request->input('ghs_per_usdt'))]
        );

        return back()->with('status', 'Conversion rate updated.');
    }

    public function approveDeposit(Request $request, Deposit $deposit, WalletService $walletService): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $deposit, $walletService): void {
                $lockedDeposit = Deposit::query()
                    ->with('user')
                    ->whereKey($deposit->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedDeposit->status !== 'PENDING') {
                    throw new \RuntimeException('Deposit is already processed.');
                }

                $lockedDeposit->status = 'APPROVED';
                $lockedDeposit->approved_at = now();
                $lockedDeposit->approved_by = $request->user()->id;
                $lockedDeposit->save();

                $walletService->credit(
                    $lockedDeposit->user,
                    $lockedDeposit->net_amount,
                    'deposit',
                    'deposit-'.$lockedDeposit->id,
                    [
                        'deposit_id' => $lockedDeposit->id,
                        'wallet_currency' => $lockedDeposit->currency,
                        'local_currency' => $lockedDeposit->local_currency,
                        'local_amount' => $lockedDeposit->local_amount,
                        'conversion_rate' => $lockedDeposit->conversion_rate,
                        'gross_amount' => $lockedDeposit->amount,
                        'fee_type' => $lockedDeposit->fee_type,
                        'fee_value' => $lockedDeposit->fee_value,
                        'fee_amount' => $lockedDeposit->fee_amount,
                        'net_amount' => $lockedDeposit->net_amount,
                        'approved_by' => $request->user()->id,
                    ],
                    $lockedDeposit->currency
                );
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['deposit' => $exception->getMessage()]);
        }

        return back()->with('status', 'Deposit approved.');
    }

    public function rejectDeposit(Request $request, Deposit $deposit): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $deposit): void {
                $lockedDeposit = Deposit::query()
                    ->whereKey($deposit->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedDeposit->status !== 'PENDING') {
                    throw new \RuntimeException('Deposit is already processed.');
                }

                $lockedDeposit->status = 'REJECTED';
                $lockedDeposit->approved_at = now();
                $lockedDeposit->approved_by = $request->user()->id;
                $lockedDeposit->save();
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['deposit' => $exception->getMessage()]);
        }

        return back()->with('status', 'Deposit rejected.');
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal, WalletService $walletService): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $withdrawal, $walletService): void {
                $lockedWithdrawal = Withdrawal::query()
                    ->with('user')
                    ->whereKey($withdrawal->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedWithdrawal->status !== 'PENDING') {
                    throw new \RuntimeException('Withdrawal is already processed.');
                }

                $lockedWithdrawal->status = 'APPROVED';
                $lockedWithdrawal->approved_at = now();
                $lockedWithdrawal->approved_by = $request->user()->id;
                $lockedWithdrawal->save();

                $reference = 'withdrawal-'.$lockedWithdrawal->id;
                $hasReferencedHold = Transaction::query()
                    ->where('type', 'withdrawal_hold')
                    ->where('reference', $reference)
                    ->exists();

                if ($hasReferencedHold) {
                    $walletService->consumeHold(
                        $lockedWithdrawal->user,
                        $lockedWithdrawal->amount,
                        $reference,
                        [
                            'withdrawal_id' => $lockedWithdrawal->id,
                            'wallet_currency' => $lockedWithdrawal->currency,
                            'local_currency' => $lockedWithdrawal->local_currency,
                            'local_amount' => $lockedWithdrawal->local_amount,
                            'conversion_rate' => $lockedWithdrawal->conversion_rate,
                            'gross_amount' => $lockedWithdrawal->amount,
                            'fee_type' => $lockedWithdrawal->fee_type,
                            'fee_value' => $lockedWithdrawal->fee_value,
                            'fee_amount' => $lockedWithdrawal->fee_amount,
                            'net_amount' => $lockedWithdrawal->net_amount,
                            'approved_by' => $request->user()->id,
                        ],
                        $lockedWithdrawal->currency
                    );
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['withdrawal' => $exception->getMessage()]);
        }

        return back()->with('status', 'Withdrawal approved.');
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal, WalletService $walletService): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $withdrawal, $walletService): void {
                $lockedWithdrawal = Withdrawal::query()
                    ->with('user')
                    ->whereKey($withdrawal->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedWithdrawal->status !== 'PENDING') {
                    throw new \RuntimeException('Withdrawal is already processed.');
                }

                $lockedWithdrawal->status = 'REJECTED';
                $lockedWithdrawal->approved_at = now();
                $lockedWithdrawal->approved_by = $request->user()->id;
                $lockedWithdrawal->save();

                $reference = 'withdrawal-'.$lockedWithdrawal->id;
                $hasReferencedHold = Transaction::query()
                    ->where('type', 'withdrawal_hold')
                    ->where('reference', $reference)
                    ->exists();

                if ($hasReferencedHold) {
                    $walletService->releaseHold(
                        $lockedWithdrawal->user,
                        $lockedWithdrawal->amount,
                        $reference,
                        [
                            'withdrawal_id' => $lockedWithdrawal->id,
                            'wallet_currency' => $lockedWithdrawal->currency,
                            'local_currency' => $lockedWithdrawal->local_currency,
                            'local_amount' => $lockedWithdrawal->local_amount,
                            'conversion_rate' => $lockedWithdrawal->conversion_rate,
                            'gross_amount' => $lockedWithdrawal->amount,
                            'fee_type' => $lockedWithdrawal->fee_type,
                            'fee_value' => $lockedWithdrawal->fee_value,
                            'fee_amount' => $lockedWithdrawal->fee_amount,
                            'net_amount' => $lockedWithdrawal->net_amount,
                            'rejected_by' => $request->user()->id,
                        ],
                        $lockedWithdrawal->currency
                    );
                } else {
                    $walletService->credit(
                        $lockedWithdrawal->user,
                        $lockedWithdrawal->amount,
                        'withdrawal_reversal',
                        $reference,
                        [
                            'withdrawal_id' => $lockedWithdrawal->id,
                            'wallet_currency' => $lockedWithdrawal->currency,
                            'local_currency' => $lockedWithdrawal->local_currency,
                            'local_amount' => $lockedWithdrawal->local_amount,
                            'conversion_rate' => $lockedWithdrawal->conversion_rate,
                            'rejected_by' => $request->user()->id,
                            'legacy_mode' => true,
                        ],
                        $lockedWithdrawal->currency
                    );
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['withdrawal' => $exception->getMessage()]);
        }

        return back()->with('status', 'Withdrawal rejected and funds restored.');
    }

    public function adjustUserBalance(User $user, AdminBalanceAdjustRequest $request, WalletService $walletService): RedirectResponse
    {
        try {
            $walletService->adjust(
                $user,
                $request->input('delta'),
                'admin_adjustment',
                'admin-adjust-'.$user->id.'-'.Str::uuid(),
                ['reason' => $request->input('reason')],
                (string) $request->input('wallet_currency')
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['delta' => $exception->getMessage()]);
        }

        return back()->with('status', 'Balance adjusted.');
    }

    private function normalizeDecimal(string|int|float|null $value, int $scale = 8): string
    {
        if ($value === null || $value === '') {
            return number_format(0, $scale, '.', '');
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        if (! is_numeric($value)) {
            throw new \RuntimeException('Invalid numeric value.');
        }

        if (function_exists('bcadd')) {
            return bcadd($value, '0', $scale);
        }

        return number_format((float) $value, $scale, '.', '');
    }

    private function percentToRate(string $percent): string
    {
        if (function_exists('bcadd') && function_exists('bcdiv')) {
            return bcadd('1', bcdiv($percent, '100', 8), 8);
        }

        return number_format(1 + ((float) $percent / 100), 8, '.', '');
    }

    private function parseDateFilter(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function buildAdminStats(): array
    {
        $pendingDeposits = Deposit::query()->where('status', 'PENDING')->count();
        $pendingWithdrawals = Withdrawal::query()->where('status', 'PENDING')->count();
        $pendingTrades = Trade::query()->where('status', 'PENDING')->count();
        $approvedDepositsToday = (string) (Deposit::query()
            ->where('status', 'APPROVED')
            ->whereDate('approved_at', now()->toDateString())
            ->sum('local_amount') ?? '0');
        $approvedWithdrawalsToday = (string) (Withdrawal::query()
            ->where('status', 'APPROVED')
            ->whereDate('approved_at', now()->toDateString())
            ->sum('local_amount') ?? '0');
        $totalBalance = (string) (Wallet::query()->sum('balance') ?? '0');

        return [
            'pending_deposits' => $pendingDeposits,
            'pending_withdrawals' => $pendingWithdrawals,
            'pending_trades' => $pendingTrades,
            'approved_deposits_today' => $this->normalizeDecimal($approvedDepositsToday),
            'approved_withdrawals_today' => $this->normalizeDecimal($approvedWithdrawalsToday),
            'total_user_balance' => $this->normalizeDecimal($totalBalance),
        ];
    }
}
