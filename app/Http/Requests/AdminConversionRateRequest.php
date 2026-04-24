<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminConversionRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ghs_per_usdt' => ['required', 'numeric', 'decimal:0,8', 'gt:0'],
        ];
    }
}
