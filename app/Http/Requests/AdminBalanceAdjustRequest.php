<?php

namespace App\Http\Requests;

use App\Support\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBalanceAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_currency' => ['required', 'string', Rule::in(WalletCurrency::all())],
            'delta' => ['required', 'numeric', 'decimal:0,8', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
