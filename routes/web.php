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
    Route::get('/oauth/authorize', 'Clients\ClientsController@authorize');

});

Route::get('/callback', function (Request $request) {

    $http = new GuzzleHttp\Client;

    $response = $http->post(url('oauth/token'), [
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => 4,
            'client_secret' => '9EExdra0TIOw2rNUb1XMWQhFinFD1DKgaXwDVc9n',
            'redirect_uri' => url('callback'),
            'code' => $request->code,
        ]
    ]);

    return json_decode((string) $response->getBody(), true);
});