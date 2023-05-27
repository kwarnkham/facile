<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorePictureRequest extends FormRequest
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
            'pictures' => ['required', 'array'],
            'pictures.*' => ['required', 'image'],
            'type' => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = ucfirst(strtolower($value));
                    if (!file_exists(app_path('Models/' . $value . '.php'))) {
                        $fail('The ' . $attribute . ' file is invalid.');
                    } else if (!method_exists('\\App\\Models\\' . $value, 'pictures')) {
                        $fail('The ' . $attribute . ' model is invalid.');
                    }
                }
            ],
            'type_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $table = strtolower($this->type) . 's';
                    if (!Schema::hasTable($table)) $fail('The ' . $attribute . ' table is invalid.');
                    else if (!DB::connection('tenant')->table($table)->where('id', $value)->exists()) $fail('The ' . $attribute . ' is invalid.');
                }
            ]
        ];
    }
}
