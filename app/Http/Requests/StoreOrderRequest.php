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
        return $this->user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'customer' => ['sometimes', 'required', 'string'],
            'phone' => ['sometimes', 'required', 'numeric'],
            'address' => ['sometimes', 'required', 'string'],
            'note' => ['sometimes', 'required', 'string'],
            'discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'features' => ['required', 'array'],
            'features.*' => ['required', 'array'],
            'features.*.id' => ['required', 'exists:features,id', 'distinct'],
            'features.*.quantity' => ['required', 'numeric', 'gt:0']
        ];
    }
}
