<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('login', function () {
    return view('login');
})->name('login');

Route::post('/user/login', 'User\UserController@login');


Route::prefix('clients')->group(function () {
    Route::get('/login', function () {
        return view('vendor.passport.login');
    });

    Route::get('/', function () {
        return view('vendor.passport.clients');
    });

    Route::get('/personal_access', function () {
        return view('vendor.passport.create');
    });

    Route::get('/oauth2/authorize', 'Clients\ClientsController@redirect');
    Route::get('/oauth2/redirect', 'Clients\ClientsController@prepareAuthorize');
    Route::post('/oauth2/access_token', 'Clients\ClientsController@authorize');
    Route::post('/oauth2/refresh_token', 'Clients\ClientsController@refreshToken');

});

Route::get('/callback', function (Request $request) {
dd($request->all());
    $http = new GuzzleHttp\Client;

    $params = [
        'grant_type' => 'authorization_code',
        'appid' => 'MS92L0lwL3Z6VHVXSHZsTGprNGNsUT09',
        'secret' => '9EExdra0TIOw2rNUb1XMWQhFinFD1DKgaXwDVc9n',
        'code' => $request->code,
    ];

    $response = $http->get(url('/clients/oauth2/access_token') . '?' . http_build_query($params));

    return json_decode((string) $response->getBody(), true);
});