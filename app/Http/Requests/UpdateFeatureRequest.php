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
        return $this->user()->hasRole('merchant') && $this->item_id == $this->feature->item_id;
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
            'name' => ['required', 'string', Rule::unique('features', 'name')->where('item_id', $this->item_id)->ignore($this->feature->id)],
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required', 'string']
        ];
    }
}
