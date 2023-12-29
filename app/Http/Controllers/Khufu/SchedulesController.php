<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Khufu\Schedule\CreateRequest;
use App\Http\Requests\Khufu\Schedule\SearchRequest;
use App\Http\Resources\Khufu\Schedule\ProductResource;
use App\Models\Khufu\Product;
use App\Models\Khufu\Schedule;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class SchedulesController extends Controller
{
    public function search(SearchRequest $request) {
        return ProductResource::collection($this->getAvailableProducts($request->start_at, $request->end_at));
    }
    
    private function getAvailableProducts($start_at, $end_at) {
        // Format dates
        $formattedStartAt = Carbon::createFromFormat('Y-m-d H:i', $start_at)->startOfDay();
        $formattedEndAt = Carbon::createFromFormat('Y-m-d H:i', $end_at)->endOfDay();


        // get booked product_ids
        $bookedProducts = Schedule::where(function ($query) use ($formattedStartAt, $formattedEndAt) {
                                $query->whereBetween('start_at', [$formattedStartAt, $formattedEndAt])
                                    ->orWhereBetween('end_at', [$formattedStartAt, $formattedEndAt]);
                            })
                            ->orWhere(function ($query) use ($formattedStartAt, $formattedEndAt) {
                                $query->where('start_at', '<', $formattedStartAt)
                                    ->where('end_at', '>', $formattedEndAt);
                            })
                            ->pluck('product_id')->toArray();
        
        // get available products
        return Product::whereNotIn('id', $bookedProducts)->get();
    }
    
    public function create(CreateRequest $request) {
        // user info
        $customerName = $request->name;
        $customerEmail = $request->email;
        $customerTel = $request->tel;

        // schedul info
        $productId = $request->product_id;
        $start_at = $request->start_at;
        $end_at = $request->end_at;
        $total_fee = $request->total_fee;
        $customfields = $request->customfields;

        // check if the product is available during the selected hours
        $availableProducts = $this->getAvailableProducts($start_at, $end_at);
        Log::info($availableProducts);

        return $request;
        // if (in_array($productId, $availableProducts)) {
            
        // }
    }
}
