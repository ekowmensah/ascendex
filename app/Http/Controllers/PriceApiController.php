<?php

namespace App\Http\Controllers;

use App\Services\PriceFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceApiController extends Controller
{
    public function latest(Request $request, PriceFeedService $priceFeedService): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['nullable', 'string', 'in:'.implode(',', PriceFeedService::supportedSymbols())],
            'limit' => ['nullable', 'integer', 'min:120', 'max:7200'],
        ]);

        $symbol = $validated['symbol'] ?? 'BTCUSDT';
        $limit = (int) ($validated['limit'] ?? 3600);
        $status = $priceFeedService->symbolStatus($symbol);

        return response()->json([
            'symbol' => $symbol,
            'price' => $status['price'],
            'series' => $priceFeedService->latestSeries($symbol, $limit),
            'tick_time' => $status['tick_time'],
            'age_seconds' => $status['age_seconds'],
            'age_label' => $status['age_label'],
            'is_fresh' => $status['is_fresh'],
            'max_age_seconds' => $status['max_age_seconds'],
        ]);
    }
}
