<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Khufu\ProductCreateRequest;
use App\Http\Requests\Khufu\ProductReadRequest;
use App\Http\Requests\Khufu\ProductUpdateRequest;
use App\Models\Khufu\Product;

class ProductsController extends Controller
{
    public function create(ProductCreateRequest $request){

        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $customfields = $request->customfields;

        $newProduct = Product::create([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'status' => 1,
            'custom_field' => $customfields,
        ]);

        return $newProduct;
    }

    public function read(ProductReadRequest $request){
        return Product::find($request->id);
    }

    public function index() {
        return Product::all();
    }

    public function update(ProductUpdateRequest $request){
        $id = $request->id;
        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $customfields = $request->customfields;

        $product = Product::find($id);

        $product->update([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'custom_field' => $customfields,
        ]);

        return $product;
    }

    public function delete(ProductReadRequest $request){
        return Product::find($request->id)->delete();
    }
}
