<?php

namespace App\Http\Requests;

use App\Support\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceTradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'in:BTCUSDT,ETHUSDT'],
            'wallet_currency' => ['required', 'string', Rule::in(WalletCurrency::all())],
            'amount' => ['required', 'numeric', 'decimal:0,8', 'gt:0'],
            'duration' => ['required', 'integer', 'in:1,5,15'],
            'direction' => ['required', 'string', 'in:UP,DOWN'],
        ];
    }
}
