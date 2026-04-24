<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $walletService)
    {
        $user = $request->user();
        $wallets = $walletService->ensureSupportedWallets($user)->sortBy('currency')->values();

        return view('wallet.index', [
            'wallets' => $wallets,
            'transactions' => Transaction::query()
                ->with('wallet')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(20),
        ]);
    }
}
