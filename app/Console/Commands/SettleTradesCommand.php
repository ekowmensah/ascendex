<?php

namespace App\Console\Commands;

use App\Services\TradeService;
use Illuminate\Console\Command;

class SettleTradesCommand extends Command
{
    protected $signature = 'trades:settle';

    protected $description = 'Settle expired binary option trades.';

    public function handle(TradeService $tradeService): int
    {
        try {
            $settled = $tradeService->settleExpiredTrades();
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Trade settlement failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Trades settled: '.$settled);

        return self::SUCCESS;
    }
}
