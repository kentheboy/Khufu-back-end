<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Khufu\ProductCreateRequest;
use App\Models\Khufu\Product;

class ProductsController extends Controller
{
    public function create(ProductCreateRequest $request){

        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $customfields = $request->customfields;

        Product::create([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'status' => 1,
            'custom_field' => $customfields,
        ]);

        $response = [
            'msg' => "test api request successfully made"
        ];

        return response($response);
    }
}
