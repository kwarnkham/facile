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
        return $this->user()->hasRole('merchant');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $this->user()->id)],
            'name' => ['required', 'string', Rule::unique('features', 'name')->where('item_id', $this->item_id)],
            'stock' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],

        ];
    }
}
