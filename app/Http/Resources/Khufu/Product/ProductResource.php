<?php

namespace App\Http\Resources\Khufu\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            "id" => $this->id,
            "name" => $this->name,
            "main_image" => $this->main_image,
            "startDate" => $this->start_at,
            "endDate" => $this->end_at,
            "status" => $this->status,
            "customfields" => $this->customfields,
        ];
    }
}
