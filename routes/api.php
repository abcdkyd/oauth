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

Route::prefix('user')->middleware('client')->group(function () {
    Route::get('/info', 'User\AdminUserController@getUserInfo');
});