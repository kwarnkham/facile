<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeatureRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::unique('features', 'name')->where('item_id', $this->item_id)],
            'stock' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required', 'string'],
            'purchase_price' => ['required', 'numeric'],
            'type' => ['sometimes', 'required', 'in:1,2'],
            'expired_on' => ['sometimes', 'required', 'date']
        ];
    }
}
