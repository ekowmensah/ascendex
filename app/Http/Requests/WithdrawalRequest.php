<?php

namespace App\Http\Requests;

use App\Support\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1'],
            'wallet_currency' => ['required', 'string', Rule::in(WalletCurrency::all())],
            'payment_method' => ['required', 'string', 'in:mobile_money'],
            'mobile_provider' => ['required', 'string', 'in:mtn,airtel,tigo,vodafone,other'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'destination' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
