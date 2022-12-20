<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureRequest extends FormRequest
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
            'name' => ['required', Rule::unique('features', 'name')->where('item_id', $this->item_id)->ignore($this->feature->id)],
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required']
        ];
    }
}
