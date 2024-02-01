<?php

namespace App\Console\Commands;

use App\Models\Khufu\Product;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class CheckProductStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-product-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the Product is ready to be published/unpublished, and change the status automatically.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->publishProducts();
        $this->unPublishProducts();
    }
    
    private function publishProducts() {
        // Get all products with a start_at date that has passed
        $unPublishedProducts = Product::where('start_at', '<', Carbon::now())
                        ->where('status', 0)
                        ->get();
    
        // Loop through the unPublishedProducts and update the status column
        foreach ($unPublishedProducts as $product) {
            $product->status = 1;
            $prodcutInfoStr = json_encode([
                "productId" => $product->id,
                "productName" => $product->name,
                "productStartDate" => $product->start_at
            ]);
            Log::info('Product published: ' . $prodcutInfoStr);
            $product->save();
        }
    }

    private function unPublishProducts() {
        // Get all products with a start_at date that has passed
        $overPublishedProducts = Product::where('end_at', '<', Carbon::now())
                        ->where('status', 1)
                        ->get();
    
        // Loop through the unPublishedProducts and update the status column
        foreach ($overPublishedProducts as $product) {
            $product->status = 0;
            $prodcutInfoStr = json_encode([
                "productId" => $product->id,
                "productName" => $product->name,
                "productStartDate" => $product->start_at
            ]);
            Log::info('Product published: ' . $prodcutInfoStr);
            $product->save();
        }
    }
}
