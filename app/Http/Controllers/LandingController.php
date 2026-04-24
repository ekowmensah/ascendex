<?php

namespace App\Http\Controllers;

use App\Services\CurrencyConversionService;
use App\Services\MarketRatesService;
use App\Services\PriceFeedService;

class LandingController extends Controller
{
    public function __invoke(
        PriceFeedService $priceFeedService,
        CurrencyConversionService $currencyConversionService,
        MarketRatesService $marketRatesService,
    ) {
        $walletOptions = $currencyConversionService->getWalletCurrencyOptions();
        $supportedSymbols = PriceFeedService::supportedSymbols();
        $ghsPerUsdt = (float) $currencyConversionService->getGhsPerUsdt();

        $symbolStatuses = collect($supportedSymbols)->mapWithKeys(
            fn (string $symbol) => [$symbol => $priceFeedService->symbolStatus($symbol)]
        )->all();

        $coinRows = $marketRatesService->snapshot($ghsPerUsdt);

        return view('landing', [
            'walletOptions' => $walletOptions,
            'symbolStatuses' => $symbolStatuses,
            'localCurrency' => CurrencyConversionService::LOCAL_CURRENCY,
            'marketRatesApiEndpoint' => url('/api/markets/rates'),
            'coinRows' => $coinRows,
            'ghsPerUsdt' => $ghsPerUsdt,
        ]);
    }
}
