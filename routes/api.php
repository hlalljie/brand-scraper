<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('brand_scraper', [App\Http\Controllers\BrandScraperController::class, 'index'])->name('brandScraper');
Route::post('test', [App\Http\Controllers\TestController::class, 'index'])->name('test');
