<?php

namespace App\Console\Commands;

use App\Services\PriceFeedService;
use Illuminate\Console\Command;

class PrunePriceTicksCommand extends Command
{
    protected $signature = 'prices:prune {--hours=24 : Keep only the latest N hours of ticks}';

    protected $description = 'Prune old price ticks to keep storage bounded.';

    public function handle(PriceFeedService $priceFeedService): int
    {
        $hours = max((int) $this->option('hours'), 1);

        try {
            $deleted = $priceFeedService->pruneOldTicks($hours);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Price prune failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Ticks pruned: {$deleted}");

        return self::SUCCESS;
    }
}
