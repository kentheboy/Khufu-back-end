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
                'otherRequests' => property_exists($customfields, 'otherRequests') ? $customfields->otherRequests : ''
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

        // prepare option texts
        $otherRequests = property_exists($customfields, "otherRequests") ? $customfields->otherRequests : '';
        $optionTextUseOfBabySheet = "{$customfields->addtionalOptions->babySeats}台";
        $optionTextUseOfChildSheet = "{$customfields->addtionalOptions->childSeats}台";
        $optionTextUseOfJuniorSheet = "{$customfields->addtionalOptions->juniorSeats}台";
        $optionTextUseOfBabyCart = "{$customfields->addtionalOptions->babyCart}台";
        $reservationMethod = property_exists($customfields, "reservationMethod") ? $customfields->reservationMethod : "一般";

        $carDetail = json_decode($productInfo->customfields);

        $this->sendAdminSlackNotice([
            "type" => "mrkdwn",
            "text" => "<!channel> 予約が入りました！
                \n*予約内容*:\n>予約ID：$scheduleInfo->id\n>時間：$scheduleInfo->start_at ~ $scheduleInfo->end_at\n>予約内容合計金額：$scheduleInfo->total_fee
                \n*お客様情報*:\n>お名前：$customerInfo->name\n>メールアドレス：$customerInfo->email\n>電話番号：$customerTel\n
                \n*お客様特記事項*:\n>$otherRequests
                \n*オプション利用数*:\n>ベビーシート：$optionTextUseOfBabySheet\n>チャイルドシート：$optionTextUseOfChildSheet\n>ジュニアシート：$optionTextUseOfJuniorSheet\n>ベビーカート：$optionTextUseOfBabyCart
                \n*その他*:\n>到着便番号：$customfields->flightNumber\n>予約方法：$reservationMethod
                \n*車両情報*:\n>車両ID：$productInfo->id\n>車名：$productInfo->name\n>乗車定員：$carDetail->passenger
                \n*クーポン*:\n$customfields->memos
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
