<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Http\Requests\Khufu\Product\CreateRequest;
use App\Http\Resources\Khufu\Product\ProductResource;
use App\Models\Khufu\Product;

class ProductsController extends Controller
{
    public function create(CreateRequest $request)
    {

        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $start_at = Carbon::parse($request->start_date);
        $end_at = $request->end_date ? Carbon::parse($request->end_date) : null;
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
        // $today = Carbon::today();
        // if (($start_at && $start_at->gt($today)) || ($end_at && $today->gt($end_at))) {
        //     $status = 0;
        // }

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

    public function read(Request $request)
    {
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
                $imageValue = Storage::disk('public')->url("/uploads/" . $imageValue);
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        $product['images'] = json_encode($images);

        return $product;
    }

    public function index()
    {
        $products =  Product::all();

        // Get only first image's dataUrl
        foreach ($products as $product) {
            $images = json_decode($product['images']);

            if (!isset($images[0]) && empty($images[0])) {
                $product['main_image'] = null;
                continue;
            }

            try {
                $product['main_image'] = Storage::disk('public')->url("/uploads/" . $images[0]);
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        return ProductResource::collection($products);
    }

    public function productListForCustomer()
    {
        $products =  Product::where('status', 1)->get();

        // Get only first image's dataUrl
        foreach ($products as $product) {
            $images = json_decode($product['images']);

            if (!isset($images[0]) && empty($images[0])) {
                $product['main_image'] = null;
                continue;
            }

            try {
                $product['main_image'] = Storage::disk('public')->url("/uploads/" . $images[0]);
            } catch (Exception $e) {
                return Log::error($e);
            }
        }

        return ProductResource::collection($products);
    }

    public function update(Request $request)
    {
        $id = $request->id;
        $name = $request->name;
        $description = $request->description;
        $price = $request->price;
        $start_at = Carbon::parse($request->start_date);
        $end_at = Carbon::parse($request->end_date);
        $customfields = $request->customfields;
        $dataUrls = json_decode($request->images);

        $product = Product::find($id);

        // update images if needed
        $images = json_decode($product->images, true);
        $isImageUpdated = false;
        if (isset($dataUrls) && !empty($dataUrls)) {
            $images = array_filter($images, function ($value) {
                return $value !== "\0";
            });
            // check requested images
            foreach ($dataUrls as $key => $dataUrl) {
                // if image updated
                if (isset($dataUrl) && !empty($dataUrl) && substr($dataUrl, 0, 4) != "http") {
                    if (isset($images[$key]) && !empty($images[$key])) {
                        $this->deleteImage($images[$key]);
                    }
                    $images[$key] = $this->saveImageAndReturnFileName($dataUrl);
                    $isImageUpdated = true;
                }
            }
            if ($isImageUpdated) {
                $product->images = $images;
                $product->save();
            }
        }


        $product->update([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'customfields' => $customfields,
        ]);
        return $product;
    }

    public function delete(Request $request)
    {
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

    public function toggleStatus(Request $request) {
        $product = Product::find($request->id);

        if ($product->status) {
            $product->status = 0;
        } else {
            $product->status = 1;
        }

        return $product->save();
    }


    private function saveImageAndReturnFileName($dataUrl)
    {
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

    private function deleteImage($filename)
    {
        Storage::disk('public')->delete('/uploads/' . $filename);
    }
}
