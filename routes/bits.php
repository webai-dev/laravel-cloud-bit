<?php

use Illuminate\Support\Facades\Route;

Route::get('teammates','CommonController@teammates');
Route::put('metadata','CommonController@metadata');
Route::get('user','CommonController@user');
Route::post('notify','CommonController@notify');
Route::post('log', 'CommonController@log');

Route::group(['prefix' => 'files'],function(){
    Route::post('/','FileController@upload');
    Route::get('/{id}','FileController@show');
    Route::delete('/{id}','FileController@delete');
});