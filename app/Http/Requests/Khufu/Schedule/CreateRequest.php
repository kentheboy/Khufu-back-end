<?php

namespace App\Http\Requests\Khufu\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => [
                'required',
                'string',
                'max:255'
            ],
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'email' => [
                'required',
                'email',
                'max:255'
            ],
            'tel' => [
                'required',
                'regex:/(01)[0-9]{9}/'
            ],
            'start_at' => [
                'required',
                'date_format:Y-m-d H:i'
            ],
            'end_at' => [
                'required',
                'date_format:Y-m-d H:i'
            ],
            'total_fee' => [
                'required',
                'numeric'
            ],
            'customfields' => [
                'json',
            ],
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}