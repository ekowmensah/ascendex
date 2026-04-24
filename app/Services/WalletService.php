<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\WalletCurrency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function ensureSupportedWallets(User $user): Collection
    {
        return new Collection(
            array_map(
                fn (string $currency) => $this->getOrCreateUserWallet($user, $currency),
                WalletCurrency::all()
            )
        );
    }

    public function getOrCreateUserWallet(User $user, string $currency = WalletCurrency::DEFAULT): Wallet
    {
        $currency = WalletCurrency::normalize($currency);

        return $user->wallets()->firstOrCreate([
            'currency' => $currency,
        ], [
            'balance' => 0,
            'bonus' => 0,
            'locked_balance' => 0,
        ]);
    }

    public function credit(
        User $user,
        string|int|float $amount,
        string $type,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $amount = $this->normalizeDecimal($amount);
        $this->ensurePositiveAmount($amount);
        $currency = WalletCurrency::normalize($currency);

        return DB::transaction(function () use ($user, $amount, $type, $reference, $meta, $currency): Wallet {
            $this->getOrCreateUserWallet($user, $currency);
            $wallet = $this->lockWallet($user, $currency);

            $before = $this->normalizeDecimal($wallet->balance);
            $after = $this->decimalAdd($before, $amount);

            $wallet->balance = $after;
            $wallet->save();

            $this->recordTransaction($user, $wallet, $type, $amount, $before, $after, $reference, $meta);

            return $wallet;
        });
    }

    public function debit(
        User $user,
        string|int|float $amount,
        string $type,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $amount = $this->normalizeDecimal($amount);
        $this->ensurePositiveAmount($amount);
        $currency = WalletCurrency::normalize($currency);

        return DB::transaction(function () use ($user, $amount, $type, $reference, $meta, $currency): Wallet {
            $this->getOrCreateUserWallet($user, $currency);
            $wallet = $this->lockWallet($user, $currency);

            $before = $this->normalizeDecimal($wallet->balance);

            if ($this->decimalCompare($before, $amount) < 0) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $after = $this->decimalSub($before, $amount);
            $wallet->balance = $after;
            $wallet->save();

            $this->recordTransaction($user, $wallet, $type, $this->decimalNegate($amount), $before, $after, $reference, $meta);

            return $wallet;
        });
    }

    public function adjust(
        User $user,
        string|int|float $delta,
        string $type,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $delta = $this->normalizeDecimal($delta);

        return $this->decimalCompare($delta, '0') >= 0
            ? $this->credit($user, $delta, $type, $reference, $meta, $currency)
            : $this->debit($user, $this->decimalAbs($delta), $type, $reference, $meta, $currency);
    }

    public function hold(
        User $user,
        string|int|float $amount,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $amount = $this->normalizeDecimal($amount);
        $this->ensurePositiveAmount($amount);
        $currency = WalletCurrency::normalize($currency);

        return DB::transaction(function () use ($user, $amount, $reference, $meta, $currency): Wallet {
            $this->getOrCreateUserWallet($user, $currency);
            $wallet = $this->lockWallet($user, $currency);

            $beforeBalance = $this->normalizeDecimal($wallet->balance);
            $beforeLocked = $this->normalizeDecimal($wallet->locked_balance);

            if ($this->decimalCompare($beforeBalance, $amount) < 0) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $afterBalance = $this->decimalSub($beforeBalance, $amount);
            $afterLocked = $this->decimalAdd($beforeLocked, $amount);

            $wallet->balance = $afterBalance;
            $wallet->locked_balance = $afterLocked;
            $wallet->save();

            $this->recordTransaction(
                $user,
                $wallet,
                'withdrawal_hold',
                $this->decimalNegate($amount),
                $beforeBalance,
                $afterBalance,
                $reference,
                array_merge($meta, [
                    'locked_before' => $beforeLocked,
                    'locked_after' => $afterLocked,
                    'locked_delta' => $amount,
                ])
            );

            return $wallet;
        });
    }

    public function releaseHold(
        User $user,
        string|int|float $amount,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $amount = $this->normalizeDecimal($amount);
        $this->ensurePositiveAmount($amount);
        $currency = WalletCurrency::normalize($currency);

        return DB::transaction(function () use ($user, $amount, $reference, $meta, $currency): Wallet {
            $wallet = $this->lockWallet($user, $currency);

            $beforeBalance = $this->normalizeDecimal($wallet->balance);
            $beforeLocked = $this->normalizeDecimal($wallet->locked_balance);

            if ($this->decimalCompare($beforeLocked, $amount) < 0) {
                throw new \RuntimeException('Insufficient locked balance.');
            }

            $afterBalance = $this->decimalAdd($beforeBalance, $amount);
            $afterLocked = $this->decimalSub($beforeLocked, $amount);

            $wallet->balance = $afterBalance;
            $wallet->locked_balance = $afterLocked;
            $wallet->save();

            $this->recordTransaction(
                $user,
                $wallet,
                'withdrawal_release',
                $amount,
                $beforeBalance,
                $afterBalance,
                $reference,
                array_merge($meta, [
                    'locked_before' => $beforeLocked,
                    'locked_after' => $afterLocked,
                    'locked_delta' => $this->decimalNegate($amount),
                ])
            );

            return $wallet;
        });
    }

    public function consumeHold(
        User $user,
        string|int|float $amount,
        ?string $reference = null,
        array $meta = [],
        string $currency = WalletCurrency::DEFAULT
    ): Wallet {
        $amount = $this->normalizeDecimal($amount);
        $this->ensurePositiveAmount($amount);
        $currency = WalletCurrency::normalize($currency);

        return DB::transaction(function () use ($user, $amount, $reference, $meta, $currency): Wallet {
            $wallet = $this->lockWallet($user, $currency);

            $beforeBalance = $this->normalizeDecimal($wallet->balance);
            $beforeLocked = $this->normalizeDecimal($wallet->locked_balance);

            if ($this->decimalCompare($beforeLocked, $amount) < 0) {
                throw new \RuntimeException('Insufficient locked balance.');
            }

            $afterLocked = $this->decimalSub($beforeLocked, $amount);

            $wallet->locked_balance = $afterLocked;
            $wallet->save();

            $this->recordTransaction(
                $user,
                $wallet,
                'withdrawal_settle',
                '0',
                $beforeBalance,
                $beforeBalance,
                $reference,
                array_merge($meta, [
                    'locked_before' => $beforeLocked,
                    'locked_after' => $afterLocked,
                    'locked_delta' => $this->decimalNegate($amount),
                    'settled_amount' => $amount,
                ])
            );

            return $wallet;
        });
    }

    private function recordTransaction(
        User $user,
        Wallet $wallet,
        string $type,
        string $amount,
        string $before,
        string $after,
        ?string $reference,
        array $meta
    ): void {
        $payload = [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'reference' => $reference,
            'meta' => array_merge($meta, [
                'currency' => $wallet->currency,
            ]),
        ];

        if ($reference === null) {
            Transaction::query()->create($payload);

            return;
        }

        try {
            Transaction::query()->firstOrCreate(
                ['type' => $type, 'reference' => $reference],
                $payload
            );
        } catch (QueryException) {
            // Unique conflict can happen under race conditions; the first write is authoritative.
        }
    }

    private function lockWallet(User $user, string $currency = WalletCurrency::DEFAULT): Wallet
    {
        $currency = WalletCurrency::normalize($currency);

        return Wallet::query()
            ->where('user_id', $user->id)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensurePositiveAmount(string $amount): void
    {
        if ($this->decimalCompare($amount, '0') <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }
    }

    private function normalizeDecimal(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        if (! is_numeric($value)) {
            throw new \RuntimeException('Invalid numeric amount.');
        }

        if (function_exists('bcadd')) {
            return bcadd($value, '0', 8);
        }

        return number_format((float) $value, 8, '.', '');
    }

    private function decimalAdd(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 8);
        }

        return number_format((float) $left + (float) $right, 8, '.', '');
    }

    private function decimalSub(string $left, string $right): string
    {
        if (function_exists('bcsub')) {
            return bcsub($left, $right, 8);
        }

        return number_format((float) $left - (float) $right, 8, '.', '');
    }

    private function decimalCompare(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 8);
        }

        return ((float) $left) <=> ((float) $right);
    }

    private function decimalAbs(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return ltrim($value, '-');
        }

        return $value;
    }

    private function decimalNegate(string $value): string
    {
        if ($value === '0.00000000' || $value === '0') {
            return '0.00000000';
        }

        return str_starts_with($value, '-') ? ltrim($value, '-') : '-'.$value;
    }
}
