<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Http\Requests\Khufu\Product\CreateRequest;
use App\Models\Khufu\Product;

class ProductsController extends Controller
{
    public function create(CreateRequest $request){

        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $start_at = Carbon::parse($request->start_date);
        $end_at = Carbon::parse($request->end_date);
        $customfields = $request->customfields;
        $dataUrls = json_decode($request->images);

        // Get the mime type and the data from the dataUrl
        foreach ($dataUrls as $key => &$dataUrl) {

            if (!isset($dataUrl) || empty($dataUrl)) {
                continue;
            }

            $dataUrl = $this->saveImageAndReturnFileName($dataUrl);
        }

        // if today is before the start date or after the end date, switch status to false(currently unavailable)
        $status = 1;
        $today = Carbon::today();
        if (($start_at && $start_at->lte($today)) || ($end_at && $today->lte($end_at))) {
            $status = 0;
        }

        $newProduct = Product::create([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'status' => $status,
            'start_at' => $start_at,
            'end_at' => $end_at,
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
                $imageValue = $this->getDataUrlFromFile('/uploads/' . $imageValue);
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
            
            if (!isset($images[0]) && empty($images[0])) {
                $product['main_image'] = null;
                continue;
            }
            
            try {
                $dataUrl = $this->getDataUrlFromFile('/uploads/' . $images[0]);
                $product['main_image'] = $dataUrl;
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        return $products;
    }

    public function update(Request $request){
        $id = $request->id;
        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $start_at = Carbon::parse($request->start_date);
        $end_at = Carbon::parse($request->end_date);
        $customfields = $request->customfields;
        $dataUrls = json_decode($request->images);

        $product = Product::find($id);

        // delete preexisting images
        if (isset($product['images']) && !empty($product['images'])) {
            $images = json_decode($product['images']);
            
            foreach ($images as $imageKey => &$filename) {
                if (!isset($filename) || empty($filename)) {
                    continue;
                }
                $this->deleteImage($filename);
            }
        }

        
        // update the image file for each dataUrl
        foreach ($dataUrls as $key => &$dataUrl) {
            
            if (!isset($dataUrl) || empty($dataUrl)) {
                continue;
            }
            
            $dataUrl = $this->saveImageAndReturnFileName($dataUrl);
        }
        
        
        $product->update([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'customfields' => $customfields,
            'images' => json_encode($dataUrls),
        ]);
        
        // if today is before the start date or after the end date, switch status to false(currently unavailable)
        $today = Carbon::today();
        if ($start_at || $end_at) {
            $status = 1;
            if ($start_at->lte($today) || $today->lte($end_at)) {
                $status = 0;
            }
            $product->update([
                'status' => $status,
                'start_at' => $start_at,
                'end_at' => $end_at,
            ]);
        }
        return $product;
    }

    public function delete(Request $request){
        $product = Product::find($request->id);

        // delete preexisting images
        if (isset($product['images']) && !empty($product['images'])) {
            $images = json_decode($product['images']);
            
            foreach ($images as $imageKey => &$filename) {
                if (!isset($filename) || empty($filename)) {
                    continue;
                }
                $this->deleteImage($filename);
            }
        }

        return Product::find($request->id)->delete();
    }

    private function getDataUrlFromFile($file_path, $mime = '') {
        $data = Storage::disk('public')->get($file_path);
        $base64 = base64_encode($data);
        
        if (!isset($base64) || empty($base64)) {
            Log::error([
                'message' => 'The provided file path does not exist.',
                'filePath' => $file_path
            ]);
            return null;
        }

        return 'data:' . $mime . ';base64,' . $base64;
    }

    private function saveImageAndReturnFileName($dataUrl){
        // Split the data URL into its parts
        $parts = explode(',', $dataUrl);
    
        // Extract the mime type and the base64 encoded data
        $mimeType = explode(';', $parts[0])[0];
        $base64Data = $parts[1];
    
        // Decode the base64 encoded data
        $data = base64_decode($base64Data);
    
        // Save the file
        $filename = uniqid() . '.' . explode('/', $mimeType)[1];
        Storage::disk('public')->put('/uploads/' . $filename, $data);
    
        // Store the filename back into the $dataUrls array
        $dataUrl = substr($filename, 0);

        return $dataUrl;
    }

    private function deleteImage($filename) {
        Storage::disk('public')->delete('/uploads/' . $filename);
    }

}
