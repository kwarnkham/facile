<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWholesaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return !is_null($this->user()->merchant) && $this->wholesale->item_id == $this->item_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'item_id' => ['required'],
            'price' => ['required', 'numeric'],
            'quantity' => [
                'required',
                'numeric',
                Rule::unique('wholesales', 'quantity')->where('item_id', $this->item_id)->ignore($this->wholesale->id)
            ],
        ];
    }
}
