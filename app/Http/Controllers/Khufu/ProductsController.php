<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Khufu\ProductCreateRequest;

class ProductsController extends Controller
{
    public function create(ProductCreateRequest $request){

        $response = [
            'msg' => "test api request successfully made",
            'requestContent' => $request
        ];

        return response($response);
    }
}
