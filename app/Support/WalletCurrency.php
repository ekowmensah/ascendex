<?php

namespace App\Support;

class WalletCurrency
{
    public const DEFAULT = 'USDT';

    public const SUPPORTED = [
        'USDT',
        'BTC',
        'ETH',
    ];

    public static function all(): array
    {
        return self::SUPPORTED;
    }

    public static function isSupported(string $currency): bool
    {
        return in_array(strtoupper(trim($currency)), self::SUPPORTED, true);
    }

    public static function normalize(string $currency): string
    {
        $normalized = strtoupper(trim($currency));

        if (! self::isSupported($normalized)) {
            throw new \RuntimeException('Unsupported wallet currency.');
        }

        return $normalized;
    }

    public static function label(string $currency): string
    {
        return match (self::normalize($currency)) {
            'USDT' => 'Tether (USDT)',
            'BTC' => 'Bitcoin (BTC)',
            'ETH' => 'Ethereum (ETH)',
            default => self::normalize($currency),
        };
    }

    public static function marketSymbol(string $currency): ?string
    {
        return match (self::normalize($currency)) {
            'BTC' => 'BTCUSDT',
            'ETH' => 'ETHUSDT',
            default => null,
        };
    }
}
