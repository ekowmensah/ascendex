<?php

namespace App\Events;

use App\Models\PriceTick;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceTickUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(public PriceTick $tick)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('prices');
    }

    public function broadcastAs(): string
    {
        return 'tick.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->tick->symbol,
            'price' => (float) $this->tick->price,
            'tick_time' => $this->tick->tick_time->toIso8601String(),
            'timestamp' => $this->tick->tick_time->timestamp,
        ];
    }
}
