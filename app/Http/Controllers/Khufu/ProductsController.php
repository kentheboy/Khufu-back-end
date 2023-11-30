<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Khufu\ProductCreateRequest;
use App\Http\Requests\Khufu\ProductReadRequest;
use App\Http\Requests\Khufu\ProductUpdateRequest;
use App\Models\Khufu\Product;
use Illuminate\Support\Facades\Log;

class ProductsController extends Controller
{
    public function create(ProductCreateRequest $request){

        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $customfields = $request->customfields;
        $dataUrls = json_decode($request->images);

        // Get the mime type and the data from the dataUrl
        foreach ($dataUrls as $key => &$dataUrl) {
            if (!isset($dataUrl) || empty($dataUrl)) {
                continue;
            }
        
            // Split the data URL into its parts
            $parts = explode(',', $dataUrl);
        
            // Extract the mime type and the base64 encoded data
            $mimeType = explode(';', $parts[0])[0];
            $base64Data = $parts[1];
        
            // Decode the base64 encoded data
            $data = base64_decode($base64Data);
        
            // Save the file
            $filename = uniqid() . '.' . explode('/', $mimeType)[1];
            $filePath = storage_path('app/public/uploads/' . $filename);
            file_put_contents($filePath, $data);
        
            // Store the filename back into the $dataUrls array
            $dataUrl = substr($filename, 0);
        }

        $newProduct = Product::create([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'status' => 1,
            'customfields' => $customfields,
            'images' => json_encode($dataUrls),
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
