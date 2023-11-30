<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

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

    public function read(Request $request){
        $product = Product::find($request->id);
        if (!isset($product['images']) || empty($product['images'])) {
            return $product;
        }
        $images = json_decode($product['images']);

        // Get all images' dataUrl
        foreach ($images as $imageKey => &$imageValue) {
            if (!isset($imageValue) || empty($imageValue)) {
                continue;
            }

            try {
                $imageValue = $this->getDataUrlFromFile(storage_path('app/public/uploads/' . $imageValue));
                Log::info($imageValue);
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        $product['images'] = json_encode($images);

        return $product;
    }

    public function index() {
        $products =  Product::all();

        // Get only first image's dataUrl
        foreach($products as $product) {
            $images = json_decode($product['images']);
            
            if (!isset($images) && empty($images)) {
                $product['main_image'] = null;
                continue;
            }
            
            try {
                $dataUrl = $this->getDataUrlFromFile(storage_path('app/public/uploads/' . $images[0]));
                $product['main_image'] = $dataUrl;
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        return $products;
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

    public function delete(Request $request){
        return Product::find($request->id)->delete();
    }

    private function getDataUrlFromFile($file_path, $mime = '') {
        if (!is_file($file_path)) {
            throw new InvalidArgumentException('The provided file path does not exist.');
        }
    
        if (empty($mime)) {
            $mime = mime_content_type($file_path);
        }
    
        $data = file_get_contents($file_path);
        $base64 = base64_encode($data);
    
        return 'data:' . $mime . ';base64,' . $base64;
    }
}
