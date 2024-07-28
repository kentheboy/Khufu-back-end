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
    public function search(SearchRequest $request)
    {
        return ProductResource::collection($this->getAvailableProducts($request->start_at, $request->end_at));
    }

    private function getAvailableProducts($start_at, $end_at, $returnType = null)
    {
        // Format dateTimes
        $formattedStartOfDay = Carbon::parse($start_at)->toDateString();
        $formattedEndOfDay = Carbon::parse($end_at)->toDateString();


        // get booked product_ids
        $bookedProducts = Schedule::where(function ($query) use ($formattedStartOfDay, $formattedEndOfDay) {
            $query->whereBetween('start_at', [$formattedStartOfDay, $formattedEndOfDay])
                ->orWhereBetween('end_at', [$formattedStartOfDay, $formattedEndOfDay]);
        })
            ->orWhere(function ($query) use ($formattedStartOfDay, $formattedEndOfDay) {
                $query->where('start_at', '<=', $formattedStartOfDay)
                    ->where('end_at', '>=', $formattedEndOfDay);
            })
            ->pluck('product_id')->toArray();

        // get available products
        $availableProductsQuery = Product::whereNotIn('id', $bookedProducts)
            ->where('status', 1)
            ->where('start_at', '<=', $formattedStartOfDay)
            ->where(function ($query) use ($formattedEndOfDay) {
                $query->where('end_at', '>=', $formattedEndOfDay)
                    ->orWhereNull('end_at');
            })
            ->where(function ($query) use ($formattedStartOfDay, $formattedEndOfDay) {
                $query->where('end_at', '>', $formattedStartOfDay)
                      ->orWhere('start_at', '>', $formattedEndOfDay);
            });

        if ($returnType === 'id') {
            return $availableProductsQuery->pluck('id')->toArray();
        }
        return $availableProductsQuery->get();
    }

    public function create(CreateRequest $request)
    {
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
            'customfields' => json_encode($customfields)
        ]);

        $productInfo = Product::find($scheduleInfo->product_id);

        switch ($customfields->deliveryOption) {
            case 1:
                $optionTextDeliveryOption = "赤嶺駅貸出";
                break;
            case 2:
                $optionTextDeliveryOption = "那覇市内ホテル貸出";
                break;
            default:
                $optionTextDeliveryOption = "特になし";
                break;
        }
        switch ($customfields->returnOption) {
            case 1:
                $optionTextReturnOption = "赤嶺駅返却";
                break;
            case 2:
                $optionTextReturnOption = "那覇市内ホテル返却";
                break;
            default:
                $optionTextReturnOption = "特になし";
                break;
        }
        $optionTextUseOfBabySheet = "{$customfields->useOfBabySheet}台";
        $optionTextUseOfChildSheet = "{$customfields->useOfChildSheet}台";
        $optionTextUseOfJuniorSheet = "{$customfields->useOfJuniorSheet}台";
        $reservationMethod = property_exists($customfields, "reservationMethod") ? $customfields->reservationMethod : "一般";

        $this->sendAdminSlackNotice([
            "type" => "mrkdwn",
            "text" => "<!channel> 予約が入りました！
                \n*予約内容*:\n>予約ID：$scheduleInfo->id\n>時間：$scheduleInfo->start_at ~ $scheduleInfo->end_at\n>空港お出迎え時刻：$customfields->airportPickup\n>空港お見送り時刻：$customfields->airportDropoff\n>予約内容合計金額：$scheduleInfo->total_fee
                \n*お客様情報*:\n>お名前：$customerInfo->name\n>メールアドレス：$customerInfo->email\n>電話番号：$customerTel\n>人数：$customfields->passengerNumber\n>免許証番号：$customfields->licenseNumber\n>生年月日：$customfields->dob
                \n*車両情報*:\n>車両ID：$productInfo->id\n>車名：$productInfo->name
                \n*オプション情報*:\n>貸出オプション： $optionTextDeliveryOption\n>返却オプション： $optionTextReturnOption\n>ベビーシート：$optionTextUseOfBabySheet\n>チャイルドシート：$optionTextUseOfChildSheet\n>ジュニアシート：$optionTextUseOfJuniorSheet\n>予約方法：$reservationMethod
                \n*その他*:\n$customfields->memos
                \nfrom： " . config('services.app.env')
        ]);

        return $request;
    }

    private function sendAdminSlackNotice($messageContent)
    {
        $client = new Client();

        $response = $client->post(config('services.slack.webhook_url'), [
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
