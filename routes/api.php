<?php

use App\Http\Controllers\PriceApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('/prices/latest', [PriceApiController::class, 'latest']);
});
