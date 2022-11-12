<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->merchant->id == $this->item->merchant_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => [
                'required', 'string',
                Rule::unique('items', 'name')->where(fn ($query) => $query->where([
                    ['merchant_id', $this->item->merchant_id],
                    ['id', '!=', $this->item->id]
                ]))
            ],
            'price' => ['required', 'numeric'],
            'description' => ['required', 'string', 'max:255']
        ];
    }
}
