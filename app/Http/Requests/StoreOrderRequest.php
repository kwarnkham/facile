<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'customer' => ['sometimes', 'required'],
            'phone' => ['sometimes', 'required'],
            'address' => ['sometimes', 'required'],
            'note' => ['sometimes', 'required'],
            'discount' => ['sometimes', 'required', 'numeric'],
            'products' => ['sometimes', 'required', 'array'],
            'products.*' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id', 'distinct'],
            'products.*.quantity' => ['required', 'numeric', 'gt:0'],
            'products.*.discount' => ['sometimes', 'required', 'numeric'],
            'services' => ['sometimes', 'required', 'array'],
            'services.*' => ['required_with:services', 'array'],
            'services.*.id' => ['required_with:services', 'exists:services,id', 'distinct'],
            'services.*.quantity' => ['required_with:services', 'numeric', 'gt:0'],
            'services.*.discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
