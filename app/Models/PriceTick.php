<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceTick extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'price',
        'tick_time',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'tick_time' => 'datetime',
        ];
    }
}
