<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payout_percent' => ['required', 'numeric', 'decimal:0,4', 'min:70', 'max:90'],
        ];
    }
}
