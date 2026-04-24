<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Http\Request;

Route::get('/build/assets/{asset}', function (string $asset) {
    if (preg_match('/^app-[A-Za-z0-9_-]+\.css$/', $asset) === 1) {
        if (basename(Vite::asset('resources/css/app.css')) === $asset) {
            abort(404);
        }

        return redirect()->to(Vite::asset('resources/css/app.css'), 302);
    }

    if (preg_match('/^app-[A-Za-z0-9_-]+\.js$/', $asset) === 1) {
        if (basename(Vite::asset('resources/js/app.js')) === $asset) {
            abort(404);
        }

        return redirect()->to(Vite::asset('resources/js/app.js'), 302);
    }

    abort(404);
})->where('asset', '.*');

Route::any('/ascendex/{path?}', function (Request $request, ?string $path = null) {
    $target = $path ? '/'.$path : '/';
    $query = $request->getQueryString();

    if ($query !== null && $query !== '') {
        $target .= '?'.$query;
    }

    return redirect()->to($target, 307);
})->where('path', '.*');

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->name('register.perform')->middleware('throttle:10,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.perform')->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.perform');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/trade', [TradeController::class, 'index'])->name('trade.index');
    Route::post('/trade', [TradeController::class, 'store'])->name('trade.store')->middleware('throttle:20,1');
    Route::get('/trade/recent', [TradeController::class, 'recent'])->name('trade.recent');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');

    Route::get('/deposits', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposit.store')->middleware('throttle:5,1');

    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawal.index');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawal.store')->middleware('throttle:5,1');

    Route::prefix('/admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::get('/deposits', [AdminController::class, 'deposits'])->name('deposits.index');
        Route::get('/withdrawals', [AdminController::class, 'withdrawals'])->name('withdrawals.index');
        Route::get('/trades', [AdminController::class, 'trades'])->name('trades.index');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index');
        Route::post('/settings/payout', [AdminController::class, 'updatePayout'])->name('settings.payout');
        Route::post('/settings/fees', [AdminController::class, 'updateFees'])->name('settings.fees');
        Route::post('/settings/conversion', [AdminController::class, 'updateConversionRate'])->name('settings.conversion');
        Route::get('/deposits/{deposit}/proof', [AdminController::class, 'viewDepositProof'])->name('deposits.proof');
        Route::post('/deposits/{deposit}/approve', [AdminController::class, 'approveDeposit'])->name('deposits.approve');
        Route::post('/deposits/{deposit}/reject', [AdminController::class, 'rejectDeposit'])->name('deposits.reject');
        Route::post('/withdrawals/{withdrawal}/approve', [AdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [AdminController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
        Route::post('/users/{user}/adjust-balance', [AdminController::class, 'adjustUserBalance'])->name('users.adjust-balance');
    });
});
