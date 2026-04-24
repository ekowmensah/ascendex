<?php

namespace App\Console\Commands;

use App\Services\PriceFeedService;
use Illuminate\Console\Command;

class FetchPriceTicksCommand extends Command
{
    protected $signature = 'prices:fetch';

    protected $description = 'Fetch latest crypto prices and persist price ticks.';

    public function handle(PriceFeedService $priceFeedService): int
    {
        try {
            $ticks = $priceFeedService->fetchAndStoreLatestTicks();
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Price fetch failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Ticks stored: '.count($ticks));

        return self::SUCCESS;
    }
}
