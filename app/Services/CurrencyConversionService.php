<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Support\WalletCurrency;

class CurrencyConversionService
{
    public const LOCAL_CURRENCY = 'GHS';

    public function __construct(
        private readonly PriceFeedService $priceFeedService,
    ) {
    }

    public function getConfig(string $selectedWalletCurrency = WalletCurrency::DEFAULT): array
    {
        $selectedWalletCurrency = WalletCurrency::isSupported($selectedWalletCurrency)
            ? WalletCurrency::normalize($selectedWalletCurrency)
            : WalletCurrency::DEFAULT;

        $walletOptions = $this->getWalletCurrencyOptions();

        return [
            'local_currency' => self::LOCAL_CURRENCY,
            'base_wallet_currency' => WalletCurrency::DEFAULT,
            'ghs_per_usdt' => $this->getGhsPerUsdt(),
            'wallet_options' => array_values($walletOptions),
            'wallet_options_by_currency' => $walletOptions,
            'selected_wallet_currency' => $selectedWalletCurrency,
            'selected_wallet_config' => $walletOptions[$selectedWalletCurrency] ?? $walletOptions[WalletCurrency::DEFAULT] ?? null,
        ];
    }

    public function getWalletCurrencyOptions(): array
    {
        $options = [];

        foreach (WalletCurrency::all() as $walletCurrency) {
            try {
                $options[$walletCurrency] = array_merge(
                    $this->getWalletCurrencyConfig($walletCurrency),
                    ['available' => true]
                );
            } catch (\RuntimeException $exception) {
                try {
                    $options[$walletCurrency] = array_merge(
                        $this->getWalletCurrencyConfig($walletCurrency, false),
                        [
                            'available' => false,
                            'error' => $exception->getMessage(),
                        ]
                    );
                } catch (\RuntimeException) {
                    $options[$walletCurrency] = [
                        'wallet_currency' => $walletCurrency,
                        'label' => WalletCurrency::label($walletCurrency),
                        'market_symbol' => WalletCurrency::marketSymbol($walletCurrency),
                        'price_in_usdt' => null,
                        'ghs_per_wallet_unit' => null,
                        'wallet_units_per_ghs' => null,
                        'rate_label' => 'Rate unavailable',
                        'freshness_label' => 'Live market tick unavailable',
                        'is_fresh' => false,
                        'available' => false,
                        'error' => $exception->getMessage(),
                    ];
                }
            }
        }

        return $options;
    }

    public function getWalletCurrencyConfig(string $walletCurrency, bool $requireFresh = true): array
    {
        $walletCurrency = WalletCurrency::normalize($walletCurrency);
        $ghsPerUsdt = $this->getGhsPerUsdt();
        $rateSnapshot = $walletCurrency === WalletCurrency::DEFAULT
            ? [
                'price_in_usdt' => '1.00000000',
                'freshness_label' => 'Admin-managed base rate',
                'is_fresh' => true,
                'rate_updated_at' => null,
            ]
            : $this->latestWalletPriceSnapshotInUsdt($walletCurrency, $requireFresh);
        $priceInUsdt = $rateSnapshot['price_in_usdt'];
        $ghsPerWalletUnit = $this->decimalMul($ghsPerUsdt, $priceInUsdt);

        if ($this->decimalCompare($ghsPerWalletUnit, '0') <= 0) {
            throw new \RuntimeException('Conversion rate must be greater than zero.');
        }

        return [
            'wallet_currency' => $walletCurrency,
            'label' => WalletCurrency::label($walletCurrency),
            'market_symbol' => WalletCurrency::marketSymbol($walletCurrency),
            'price_in_usdt' => $priceInUsdt,
            'ghs_per_wallet_unit' => $ghsPerWalletUnit,
            'wallet_units_per_ghs' => $this->decimalDiv('1', $ghsPerWalletUnit),
            'rate_label' => '1 '.$walletCurrency.' = '.$this->formatLocal($ghsPerWalletUnit).' '.self::LOCAL_CURRENCY,
            'freshness_label' => $rateSnapshot['freshness_label'],
            'is_fresh' => $rateSnapshot['is_fresh'],
            'rate_updated_at' => $rateSnapshot['rate_updated_at'],
        ];
    }

    public function calculateSnapshotFromLocalAmount(
        string|int|float|null $localAmount,
        string $walletCurrency = WalletCurrency::DEFAULT
    ): array {
        $normalizedLocalAmount = $this->normalizeLocalAmount($localAmount);

        if ($this->decimalCompare($normalizedLocalAmount, '0') <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }

        $walletConfig = $this->getWalletCurrencyConfig($walletCurrency);
        $grossWalletAmount = $this->decimalDiv($normalizedLocalAmount, $walletConfig['ghs_per_wallet_unit']);

        if ($this->decimalCompare($grossWalletAmount, '0') <= 0) {
            throw new \RuntimeException('Converted wallet amount must be greater than zero.');
        }

        return [
            'currency' => $walletConfig['wallet_currency'],
            'wallet_currency' => $walletConfig['wallet_currency'],
            'local_currency' => self::LOCAL_CURRENCY,
            'local_amount' => $normalizedLocalAmount,
            'conversion_rate' => $walletConfig['ghs_per_wallet_unit'],
            'amount' => $grossWalletAmount,
            'price_in_usdt' => $walletConfig['price_in_usdt'],
        ];
    }

    public function convertWalletToLocal(
        string|int|float|null $walletAmount,
        string $walletCurrency = WalletCurrency::DEFAULT,
        string|int|float|null $ghsPerWalletUnit = null
    ): string {
        $normalizedWalletAmount = $this->normalizeWalletAmount($walletAmount);
        $rate = $ghsPerWalletUnit !== null
            ? $this->normalizeWalletAmount($ghsPerWalletUnit)
            : $this->getWalletCurrencyConfig($walletCurrency)['ghs_per_wallet_unit'];

        return $this->decimalMul($normalizedWalletAmount, $rate, 2);
    }

    public function getGhsPerUsdt(): string
    {
        $value = AdminSetting::getValue('ghs_per_usdt', '15.00000000');
        $normalizedValue = $this->normalizeWalletAmount($value);

        if ($this->decimalCompare($normalizedValue, '0') <= 0) {
            throw new \RuntimeException('Conversion rate must be greater than zero.');
        }

        return $normalizedValue;
    }

    private function latestWalletPriceSnapshotInUsdt(string $walletCurrency, bool $requireFresh = true): array
    {
        $marketSymbol = WalletCurrency::marketSymbol($walletCurrency);

        if ($marketSymbol === null) {
            return [
                'price_in_usdt' => '1.00000000',
                'freshness_label' => 'Admin-managed base rate',
                'is_fresh' => true,
                'rate_updated_at' => null,
            ];
        }

        $tick = $this->priceFeedService->latestTick($marketSymbol);

        if ((! $tick || ! $this->priceFeedService->isTickFresh($tick->tick_time)) && $requireFresh) {
            $this->priceFeedService->fetchAndStoreSymbolTick($marketSymbol);
            $tick = $this->priceFeedService->latestTick($marketSymbol);
        }

        if (! $tick || ! $tick->price) {
            throw new \RuntimeException('Live '.$walletCurrency.' rate unavailable. No market tick recorded yet.');
        }

        $price = $this->normalizeWalletAmount((string) $tick->price);

        if ($this->decimalCompare($price, '0') <= 0) {
            throw new \RuntimeException('Live '.$walletCurrency.' rate unavailable. Refresh price data and try again.');
        }

        $isFresh = $this->priceFeedService->isTickFresh($tick->tick_time);
        $ageLabel = $this->priceFeedService->tickAgeLabel($tick->tick_time);

        if ($requireFresh && ! $isFresh) {
            throw new \RuntimeException('Live '.$walletCurrency.' rate is stale. Last market tick: '.$ageLabel.'.');
        }

        return [
            'price_in_usdt' => $price,
            'freshness_label' => 'Last market tick: '.$ageLabel.($isFresh ? '' : ' (stale)'),
            'is_fresh' => $isFresh,
            'rate_updated_at' => $tick->tick_time?->toIso8601String(),
        ];
    }

    private function normalizeLocalAmount(string|int|float|null $value): string
    {
        return $this->normalizeDecimal($value, 2);
    }

    private function normalizeWalletAmount(string|int|float|null $value): string
    {
        return $this->normalizeDecimal($value, 8);
    }

    private function normalizeDecimal(string|int|float|null $value, int $scale): string
    {
        if ($value === null || $value === '') {
            return number_format(0, $scale, '.', '');
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        if (! is_numeric($value)) {
            throw new \RuntimeException('Invalid numeric amount.');
        }

        if (function_exists('bcadd')) {
            return bcadd($value, '0', $scale);
        }

        return number_format((float) $value, $scale, '.', '');
    }

    private function decimalMul(string $left, string $right, int $scale = 8): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, $scale);
        }

        return number_format((float) $left * (float) $right, $scale, '.', '');
    }

    private function decimalDiv(string $left, string $right, int $scale = 8): string
    {
        if ($this->decimalCompare($right, '0') === 0) {
            throw new \RuntimeException('Division by zero.');
        }

        if (function_exists('bcdiv')) {
            return bcdiv($left, $right, $scale);
        }

        return number_format((float) $left / (float) $right, $scale, '.', '');
    }

    private function decimalCompare(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 8);
        }

        return ((float) $left) <=> ((float) $right);
    }

    private function formatLocal(string $value): string
    {
        return number_format((float) $value, 2);
    }
}
