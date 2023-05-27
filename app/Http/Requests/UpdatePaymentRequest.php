<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'qr' => ['sometimes', Rule::requiredIf($this->payment_type_id != 1), 'image'],
            'account_name' => [Rule::requiredIf($this->payment_type_id != 1)],
            'number' => [
                Rule::requiredIf($this->route('payment')->payment_type_id != 1),
                Rule::unique('tenant.payments')->where('payment_type_id', $this->route('payment')->payment_type_id)->ignore($this->number, 'number')
            ],
        ];
    }
}
