<?php

namespace App\Services;

use App\Models\AdminSetting;

class SystemFeeService
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FLAT = 'flat';

    public function getDepositFeeConfig(): array
    {
        return $this->getFeeConfig('deposit');
    }

    public function getWithdrawalFeeConfig(): array
    {
        return $this->getFeeConfig('withdrawal');
    }

    public function calculateDepositSnapshot(
        string|int|float|null $localAmount,
        string|int|float|null $conversionRate
    ): array {
        return $this->calculateSnapshot('deposit', $localAmount, $conversionRate);
    }

    public function calculateWithdrawalSnapshot(
        string|int|float|null $localAmount,
        string|int|float|null $conversionRate
    ): array {
        return $this->calculateSnapshot('withdrawal', $localAmount, $conversionRate);
    }

    public function formatFeeLabel(array $config): string
    {
        $value = $this->normalizeDecimal($config['value'] ?? '0');

        if (($config['type'] ?? self::TYPE_FLAT) === self::TYPE_PERCENTAGE) {
            return $this->trimTrailingZeros($value).'%';
        }

        return number_format((float) $value, 2).' GHS flat';
    }

    private function getFeeConfig(string $prefix): array
    {
        $type = (string) AdminSetting::getValue($prefix.'_fee_type', self::TYPE_FLAT);
        $value = AdminSetting::getValue($prefix.'_fee_value', '0');

        if (! in_array($type, [self::TYPE_PERCENTAGE, self::TYPE_FLAT], true)) {
            $type = self::TYPE_FLAT;
        }

        $normalizedValue = $this->normalizeDecimal($value);

        return [
            'type' => $type,
            'value' => $normalizedValue,
            'label' => $this->formatFeeLabel([
                'type' => $type,
                'value' => $normalizedValue,
            ]),
        ];
    }

    private function calculateSnapshot(
        string $prefix,
        string|int|float|null $localAmount,
        string|int|float|null $conversionRate
    ): array {
        $grossLocalAmount = $this->normalizeDecimal($localAmount);
        $normalizedConversionRate = $this->normalizeDecimal($conversionRate);
        $config = $this->getFeeConfig($prefix);
        $grossWalletAmount = $this->decimalDiv($grossLocalAmount, $normalizedConversionRate);
        $localFeeAmount = $this->calculateFeeAmount($grossLocalAmount, $config['type'], $config['value']);
        $feeAmount = $this->decimalDiv($localFeeAmount, $normalizedConversionRate);
        $netAmount = $this->decimalSub($grossWalletAmount, $feeAmount);

        if ($this->decimalCompare($netAmount, '0') <= 0) {
            throw new \RuntimeException(
                ucfirst($prefix).' fee leaves no net amount to process. Please reduce the fee or increase the request amount.'
            );
        }

        return [
            'fee_type' => $config['type'],
            'fee_value' => $config['value'],
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'local_fee_amount' => $localFeeAmount,
        ];
    }

    private function calculateFeeAmount(string $grossAmount, string $type, string $value): string
    {
        if ($this->decimalCompare($value, '0') <= 0) {
            return '0.00000000';
        }

        if ($type === self::TYPE_PERCENTAGE) {
            return $this->decimalDiv($this->decimalMul($grossAmount, $value), '100');
        }

        return $value;
    }

    private function normalizeDecimal(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00000000';
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

    private function decimalMul(string $left, string $right): string
    {
        if (function_exists('bcmul')) {
            return bcmul($left, $right, 8);
        }

        return number_format((float) $left * (float) $right, 8, '.', '');
    }

    private function decimalDiv(string $left, string $right): string
    {
        if ($this->decimalCompare($right, '0') === 0) {
            throw new \RuntimeException('Division by zero.');
        }

        if (function_exists('bcdiv')) {
            return bcdiv($left, $right, 8);
        }

        return number_format((float) $left / (float) $right, 8, '.', '');
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

    private function trimTrailingZeros(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.');
    }
}
