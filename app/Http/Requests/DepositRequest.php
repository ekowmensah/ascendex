<?php

namespace App\Http\Requests;

use App\Support\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepositRequest extends FormRequest
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
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'transaction_reference' => ['required', 'string', 'max:100'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
