<?php

namespace App\Http\Controllers;

use App\Services\PriceFeedService;

class DashboardController extends Controller
{
    public function __invoke(PriceFeedService $priceFeedService)
    {
        return view('dashboard', [
            'btcPrice' => $priceFeedService->latestPrice('BTCUSDT'),
            'ethPrice' => $priceFeedService->latestPrice('ETHUSDT'),
        ]);
    }
}
