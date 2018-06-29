<?php
use Illuminate\Http\Request;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api']], function () {
    Route::group(['middleware' => 'jwt.auth'], function () {
        //提款到银行卡
//        Route::post('/bank/withdrawSubmit.do','Mobile\PayController@withdrawSubmit');
    });
});


