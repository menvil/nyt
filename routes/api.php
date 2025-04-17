<?php

use App\Http\Controllers\Api\V1\BestSellersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// V1 Routes
Route::prefix('v1')->group(function () {
    Route::get('best-sellers/history', [BestSellersController::class, 'history'])
        ->name('api.v1.best-sellers.history');
});
