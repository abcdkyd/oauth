<?php

use Illuminate\Http\Request;
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


Route::middleware(['auth:api'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/token', 'Clients\AdminClientsController@token');
Route::post('/user/token', 'User\AdminUserController@token');


Route::prefix('clients')->group(function () {
    Route::post('/token', 'Clients\AdminClientsController@token');
});

// 用户相关api
Route::prefix('clients')->group(function () {
    Route::prefix('user')->middleware('client')->group(function () {
        Route::get('/info', 'User\UserController@getUserInfo');
        Route::get('/openid', 'User\UserController@getUserOpenid');
    });
});

// 银联回调api
Route::middleware('client')->post('/callback/unionpay', 'Clients\ClientsController@callbackUnionpay');

