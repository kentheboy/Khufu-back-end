<?php

namespace App\Http\Requests\Khufu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductUpdateRequest extends FormRequest
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
            ],
            'name' => [
                'string',
                'max:255'
            ],
            'description' => [
                'string',
                'max:500'
            ],
            'price' => [
                'numeric'
            ],
            'customfields' => [
                'string',
            ],
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}