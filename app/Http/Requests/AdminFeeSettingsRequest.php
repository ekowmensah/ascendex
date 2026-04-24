<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminFeeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deposit_fee_type' => ['required', 'string', 'in:percentage,flat'],
            'deposit_fee_value' => ['required', 'numeric', 'decimal:0,8', 'min:0'],
            'withdrawal_fee_type' => ['required', 'string', 'in:percentage,flat'],
            'withdrawal_fee_value' => ['required', 'numeric', 'decimal:0,8', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $this->validatePercentageValue($validator, 'deposit');
            $this->validatePercentageValue($validator, 'withdrawal');
        });
    }

    private function validatePercentageValue(Validator $validator, string $prefix): void
    {
        if ($this->input($prefix.'_fee_type') !== 'percentage') {
            return;
        }

        $value = $this->input($prefix.'_fee_value');

        if (! is_numeric($value)) {
            return;
        }

        if ((float) $value >= 100) {
            $validator->errors()->add(
                $prefix.'_fee_value',
                ucfirst($prefix).' percentage fee must be less than 100%.'
            );
        }
    }
}
