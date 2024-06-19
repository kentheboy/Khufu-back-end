<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiTestController;
use App\Http\Controllers\Khufu\ProductsController;
use App\Http\Controllers\Khufu\SchedulesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', [ApiTestController::class, 'test']);

// Khufu ProductsTable
Route::post('/products/create', [ProductsController::class, 'create']);
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/customer/products', [ProductsController::class, 'productListForCustomer']);
Route::get('/products/{id}', [ProductsController::class, 'read']);
Route::patch('/products/{id}', [ProductsController::class, 'update']);
Route::delete('/products/{id}', [ProductsController::class, 'delete']);
Route::patch('/products/status/{id}', [ProductsController::class, 'toggleStatus']);

// Khufu ScheduleTable
Route::get('/schedule/search', [SchedulesController::class, 'search']);
Route::post('/schedule/create', [SchedulesController::class, 'create']);

