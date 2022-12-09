<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWholesaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !is_null($this->user()->merchant);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'item_id' => ['required', Rule::exists('items', 'id')->where('merchant_id', $this->user()->merchant->id)],
            'price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', Rule::unique('wholesales', 'quantity')->where('item_id', $this->item_id)],
        ];
    }
}
