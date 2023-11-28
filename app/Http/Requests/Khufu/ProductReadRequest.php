<?php

namespace App\Http\Requests\Khufu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProductReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return true;
    // }

    /**
     * Check if the field is valid dataUrl
     * 
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param $validator
     * @return Bool
     */
    private function dataUrlValidator($attribute, $value, $parameters, $validator)
    {
        $regex = '/^data:image\/(\w+);base64,/';

        if (!is_string($value) || !preg_match($regex, $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:products,id'
            ]
        ];
    }
    
    public function messages()
    {
        return [
            'image1.data_url' => 'The field name must be a data URL.',
            'image2.data_url' => 'The field name must be a data URL.',
            'image3.data_url' => 'The field name must be a data URL.',
            'image4.data_url' => 'The field name must be a data URL.',
        ];
    }
}