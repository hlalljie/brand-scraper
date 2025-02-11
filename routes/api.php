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

Route::post('find-styles', [App\Http\Controllers\StyleFinderController::class, 'index'])->name('styleFinder');
Route::get('/progress/{trackerId}', [App\Http\Controllers\StyleFinderController::class, 'checkProgress']);
Route::post('test', [App\Http\Controllers\TestController::class, 'index'])->name('test');
Route::get('/test/progress/{trackerId}', [App\Http\Controllers\TestController::class, 'checkProgress']);
