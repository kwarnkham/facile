<?php

namespace App\Http\Requests;

use App\Models\Feature;
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
            'features' => ['sometimes', 'required', 'array'],
            'features.*' => ['required', 'array'],
            'features.*.id' => ['required', 'exists:features,id', 'distinct'],
            'features.*.quantity' => ['required', 'numeric', 'gt:0'],
            'features.*.discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'services' => ['sometimes', 'required', 'array'],
            'services.*' => ['required_with:services', 'array'],
            'services.*.id' => ['required_with:services', 'exists:services,id', 'distinct'],
            'services.*.quantity' => ['required_with:services', 'numeric', 'gt:0'],
            'services.*.discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
