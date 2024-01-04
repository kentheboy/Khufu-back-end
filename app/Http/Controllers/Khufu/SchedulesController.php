<?php

namespace App\Http\Controllers\Khufu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

use App\Http\Requests\Khufu\Schedule\CreateRequest;
use App\Http\Requests\Khufu\Schedule\SearchRequest;
use App\Http\Resources\Khufu\Schedule\ProductResource;
use App\Models\Khufu\Product;
use App\Models\Khufu\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Log;



use Carbon\Carbon;

class SchedulesController extends Controller
{
    public function search(SearchRequest $request) {
        return ProductResource::collection($this->getAvailableProducts($request->start_at, $request->end_at));
    }
    
    private function getAvailableProducts($start_at, $end_at, $returnType = null) {
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
        if ($returnType === 'id') {
            return Product::whereNotIn('id', $bookedProducts)->pluck('id')->toArray();
        }
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
        $customfields = json_decode($request->customfields);

        // check if the product is available during the selected hours
        $availableProductIds = $this->getAvailableProducts($start_at, $end_at, 'id');
        if (!in_array($productId, $availableProductIds)) {
            return response()->json(['message' => 'The productId: {' . $productId . '} is not available.'], 400); 
        }

        // save customer information to users table.
        $customerInfo = User::create([
            'name' => $customerName,
            'email' => $customerEmail,
            'customfields' => json_encode([
                'tel' => $customerTel,
                'licenseNumber' => $customfields->licenseNumber,
                'dob' => $customfields->dob,
            ])
        ]);

        // save schedule information to schedules table.
        $scheduleInfo = Schedule::create([
            'product_id' => $productId,
            'user_id' => $customerInfo->id,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'total_fee' => $total_fee,
            'customfields' => json_encode([
                "airportPickup" => $customfields->airportPickup,
                "airportDropoff" => $customfields->airportDropoff 
            ])
        ]);

        $productInfo = Product::find($scheduleInfo->product_id);

        $this->sendAdminSlackNotice([
            "type" => "mrkdwn",
            "text" => "<!channel> 予約が入りました！
                \n*予約内容*:\n>予約ID：$scheduleInfo->id\n>時間：$scheduleInfo->start_at ~ $scheduleInfo->end_at\n>空港お出迎え時刻：$customfields->airportPickup\n>空港お見送り時刻：$customfields->airportDropoff
                \n*お客様情報*:\n>お名前：$customerInfo->name\n>メールアドレス：$customerInfo->email\n>電話番号：$customerTel\n>免許証番号：$customfields->licenseNumber\n>生年月日：$customfields->dob
                \n*車両情報*:\n>車両ID：$productInfo->id\n>車名：$productInfo->name
                \nfrom： ".env('APP_URL')
        ]);

        return $request;
    }

    private function sendAdminSlackNotice($messageContent)
    {
        $client = new Client();

        $response = $client->post(env('SLACK_WEBHOOK_URL'), [
            'json' => $messageContent,
        ]);

        if ($response->getStatusCode() == 200) {
            return [
                'message' => 'Message sent successfully',
                'status' => 200
            ];
        } else {
            Log::error(json_encode($response));
            return [
                'message' => 'Failed to send message',
                'status' => 500
            ];
        }
    }
}
