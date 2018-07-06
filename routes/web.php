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

Route::get('/', function () {
    return view('welcome');
});

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

});