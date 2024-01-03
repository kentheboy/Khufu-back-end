<?php

namespace App\Http\Resources\Khufu\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = json_decode($this->images);
        foreach($images as $key => $image) {
            if (isset($image) && !empty($image)) {
                $images[$key] = "/storage/uploads/" . $image;
            }
        }

        return [
            "id" => $this->id,
            "title" => $this->name,
            "price" => $this->price,
            "customfields" => $this->customfields,
            "images" => $images
        ];
    }
}
