<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Khufu\ProductCreateRequest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('data_url', function ($attribute, $value, $parameters, $validator) {
            return (new ProductCreateRequest)->dataUrlValidator($attribute, $value, $parameters, $validator);
        });    
    }
}
